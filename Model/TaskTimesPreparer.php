<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Model\SortingLogic;
use Kanboard\Plugin\WeekHelper\Model\TaskInfoParser;
use Kanboard\Plugin\WeekHelper\Model\TimesData;
use Kanboard\Plugin\WeekHelper\Model\TimesDataPerEntity;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerFetcher;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerTranscriber;


class TaskTimesPreparer
{
    /**
     * The config values, which are outside stored
     * in the Kanboard config database probably.
     *
     * @var array
     **/
    var $config = [
        'level_1_columns' => [],
        'level_2_columns' => [],
        'level_3_columns' => [],
        'level_4_columns' => [],
        'non_time_mode_minutes' => 0,
        // can be: 'id', 'remaining_hours_asc', 'remaining_hours_desc'
        'tooltip_sorting' => 'id',
        'sorting_logic' => '',
        'timetagger_url' => '',
        'timetagger_authtoken' => '',
        'timetagger_cookies' => '',
        'timetagger_overwrites_levels_spent' => '',
        'timetagger_start_fetch' => '',
    ];

    /**
     * All "prepared" final tasks with their ID as the key.
     *
     *  [
     *      task_id => [task_array],
     *      ...
     *  ]
     *
     * @var array
     **/
    var $tasks = [];

    /**
     * The class attribute, holding the tasks per level.
     * The value array has the task id as key and the task
     * itself as value.
     *
     *  [
     *      'level_1' => [
     *          task_id => [task_array],
     *          ...
     *      ],
     *      ...
     *  ]
     *
     * @var array
     **/
    var $tasks_per_level = [
        'level_1' => [],
        'level_2' => [],
        'level_3' => [],
        'level_4' => [],
    ];

    /**
     * Simply all times as a TimesData instance.
     *
     * @var TimesData
     **/
    var $times;

    /**
     * Times for all levels individually.
     *
     * @var TimesDataPerEntity
     **/
    var $times_per_level;

    /**
     * Times per project.
     *
     * @var TimesDataPerEntity
     **/
    var $times_per_project;

    /**
     * Times per user.
     *
     * @var TimesDataPerEntity
     **/
    var $times_per_user;

    /**
     * The TimetaggerTranscriber, which can overwrite
     * spent times for tasks.
     *
     * @var TimetaggerTranscriber
     **/
    var $timetagger_transcriber = null;

    /**
     * Construct the instance with certain settings, if needed.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->initConfig($config);
        $this->times = new TimesData();
        $this->times_per_level = new TimesDataPerEntity();
        $this->times_per_project = new TimesDataPerEntity();
        $this->times_per_user = new TimesDataPerEntity();
    }

    /**
     * Clear internal values and make them "empty".
     */
    protected function emptyInternalValues()
    {
        $this->tasks = [];
        $this->tasks_per_level = [
            'level_1' => [],
            'level_2' => [],
            'level_3' => [],
            'level_4' => [],
        ];
        $this->times->resetTimes();
        $this->times_per_level->resetTimes();
        $this->times_per_project->resetTimes();
        $this->times_per_user->resetTimes();
    }

    /**
     * Extend the given subtasks array and add 'percentage'
     * to their keys. The logic is basically that a subtasks
     * title can contain a percentage string like "30 %" or "30%"
     * which would tell the system how much this subtask occupies
     * in time of the whole.
     *
     * exmaple 1:
     * 5 subtasks with no percentages. this means that every subtask
     * should have 20% automatically.
     *
     * example 2:
     * 5 subtasks with 1 with 40% and 1 with 10%. this means that
     * these two already occupy 50% of all subtasks. means that
     * the remaining 3 subtasks have to share 50%, means that one
     * subtask of them is 16,6% of the whole subtasks sum.
     *
     * @param  array &$subtasks
     */
    protected static function extendSubtasksWithPercentage(&$subtasks)
    {
        $countWithout = 0;
        $percentRemaining = 1.0;

        // first run: parse, set known percentages, count unknowns
        foreach ($subtasks as $k => $s) {
            $p = self::getPercentFromString($s['title']);
            if ($p != -1) {
                $subtasks[$k]['percentage'] = $p;
                $percentRemaining -= $p;
            } else {
                // mark otherwise, assigning later, count increasing
                $subtasks[$k]['percentage'] = null;
                $countWithout++;
            }
        }

        if ($countWithout === 0) {
            return;
        }

        // rounding fixing
        if ($percentRemaining <= 0.0) {
            $fill = 0.0;
        } else {
            $fill = $percentRemaining / $countWithout;
        }

        // fill in subtasks without given percentage
        foreach ($subtasks as $k => $s) {
            if ($s['percentage'] === null) {
                $subtasks[$k]['percentage'] = $fill;
            }
        }
    }

    /**
     * Extend the parsed info for the given tasks.
     * A task can have certain values given in the description text,
     * which can be parsed into task array keys. e.g. "project_type"
     * can be overwritten here, etc.
     *
     * @param  array &$tasks
     */
    protected static function extendTasksInfo(&$tasks)
    {
        foreach ($tasks as &$task) {
            TaskInfoParser::extendTask($task);
        }
        unset($task);
    }

    /**
     * Represent the given float as a proper time string.
     *
     * @param  float $time
     * @return string
     */
    protected static function floatToHHMM($time)
    {
        if ($time < 0) {
            $time = $time * -1;
            $negative = true;
        } else {
            $negative = false;
        }
        $hours = (int) $time;
        $minutes = fmod((float) $time, 1) * 60;
        if ($negative) {
            return sprintf('-%01d:%02d', $hours, $minutes);
        } else {
            return sprintf('%01d:%02d', $hours, $minutes);
        }
    }

    /**
     * Init the config with the given array.
     *
     * @param  array  $config
     */
    protected function initConfig($config = [])
    {
        $this->config = array_merge($this->config, $config);
        // if one is a string, the others are, too, probably
        if (is_string($this->config['level_1_columns'])) {
            $this->config['level_1_columns'] = explode(',', $this->config['level_1_columns']);
            $this->config['level_2_columns'] = explode(',', $this->config['level_2_columns']);
            $this->config['level_3_columns'] = explode(',', $this->config['level_3_columns']);
            $this->config['level_4_columns'] = explode(',', $this->config['level_4_columns']);
        }
    }

    /**
     * Initialize the overtime for the given task.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @return float
     */
    protected function initOvertimeTimeForTask(&$task = [], $subtasks = [])
    {
        $over_time = 0.0;
        if (isset($task['id'])) {

            // calculate remaining or overtime based on subtasks
            if (!empty($subtasks)) {
                $tmp = $this->getOvertimeFromSubtasks($subtasks);

            // calculate remaining or overtime based only on task itself
            } else {
                $tmp = $task['time_spent'] - $task['time_estimated'];
            }

            $over_time = $tmp;

            // also add the remaining time, which otherwise
            // would generate an overtime, which is not wanted
            $over_time += $this->getRemainingTimeForTask($task);
        }
        $task['time_overtime'] = round($over_time, 2);
        return $task['time_overtime'];
    }

    /**
     * Initialize the remaining time for the given task.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @return float
     */
    protected function initRemainingTimeForTask(&$task = [], $subtasks = [])
    {
        $remaining_time = 0.0;
        if (isset($task['id'])) {
            $task['open_subtasks'] = 0;

            // calculate remaining or overtime based on subtasks
            if (!empty($subtasks)) {
                $tmp = $this->getRemainingFromSubtasks($subtasks, $task);

            // calculate remaining or overtime based only on task itself
            } else {
                $tmp = self::remainingCalculation($task);
            }

            // remaining time should be positive
            if ($tmp > 0) {
                $remaining_time = $tmp;
            }
        }
        $task['time_remaining'] = round($remaining_time, 2);
        return $task['time_remaining'];
    }

    /**
     * Initialize all the important internal cache variables
     * with the given tasks and subtasks.
     *
     * @param  array  &$tasks
     * @param  array  $subtasks_by_task_id Key is task id and values are the subtasks
     */
    public function initTasksAndTimes(&$tasks = [], $subtasks_by_task_id = [])
    {
        self::extendTasksInfo($tasks);
        $this->emptyInternalValues();

        foreach ($tasks as $i => &$task) {

            // getting the tasks subtasks
            if (array_key_exists($task['id'], $subtasks_by_task_id)) {
                $subtasks = $subtasks_by_task_id[$task['id']];
            } else {
                $subtasks = [];
            }

            // getting (and automatically setting internally) the task times
            $estimated = $this->getEstimatedTimeForTask($task);
            $spent = $this->getSpentTimeForTask($task, $subtasks);
            $remaining = $this->getRemainingTimeForTask($task, $subtasks);
            $overtime = $this->getOvertimeForTask($task, $subtasks);

            // add the tasks to some internal variables (by reference)
            $this->tasks[$task['id']] = &$tasks[$i];
            if ($this->inSwimlaneColumn($task, 'level_1')) {
                $this->tasks_per_level['level_1'][$task['id']] = &$tasks[$i];
            }
            if ($this->inSwimlaneColumn($task, 'level_2')) {
                $this->tasks_per_level['level_2'][$task['id']] = &$tasks[$i];
            }
            if ($this->inSwimlaneColumn($task, 'level_3')) {
                $this->tasks_per_level['level_3'][$task['id']] = &$tasks[$i];
            }
            if ($this->inSwimlaneColumn($task, 'level_4')) {
                $this->tasks_per_level['level_4'][$task['id']] = &$tasks[$i];
            }

            // == == == == == == == ==
            // ADDING TIMES   -  START
            // == == == == == == == ==

            $this->times->addTimes($estimated, $spent, $remaining, $overtime);

            if ($this->inSwimlaneColumn($task, 'level_1')) {
                $this->times_per_level->addTimes($estimated, $spent, $remaining, $overtime, 'level_1');
            }
            if ($this->inSwimlaneColumn($task, 'level_2')) {
                $this->times_per_level->addTimes($estimated, $spent, $remaining, $overtime, 'level_2');
            }
            if ($this->inSwimlaneColumn($task, 'level_3')) {
                $this->times_per_level->addTimes($estimated, $spent, $remaining, $overtime, 'level_3');
            }
            if ($this->inSwimlaneColumn($task, 'level_4')) {
                $this->times_per_level->addTimes($estimated, $spent, $remaining, $overtime, 'level_4');
            }

            $this->times_per_project->addTimes($estimated, $spent, $remaining, $overtime, $task['project_id']);

            $this->times_per_user->addTimes($estimated, $spent, $remaining, $overtime, $task['owner_id']);

            // == == == == == == ==
            // ADDING TIMES  -  END
            // == == == == == == ==

        }
        unset($task);
    }

    /**
     * Fetch Timetagger events and set the internal TimetaggerTranscriber.
     */
    protected function initTimetagger()
    {
        $timetagger_fetcher = new TimetaggerFetcher(
            $this->getConfig('timetagger_url'),
            $this->getConfig('timetagger_authtoken'),
            $this->getConfig('timetagger_cookies')
        );
        $timetagger_fetcher->fetchEvents(
            strtotime($this->getConfig('timetagger_start_fetch'))
        );
        $this->timetagger_transcriber = new TimetaggerTranscriber($timetagger_fetcher);
    }

    /**
     * Checks for the given level, if the task is in the correct
     * swimlane and / or column.
     *
     * @param  array $task
     * @param  string $level
     * @return boolean
     */
    public function inSwimlaneColumn($task, $level)
    {
        if (!str_contains($level, '_columns')) {
            $level .= '_columns';
        }
        $config = $this->config[$level] ?? '';
        $swimlane = $task['swimlane_name'] ?? '';
        $column = $task['column_name'] ?? '';
        return self::swimlaneColumnCheck($config, $swimlane, $column);
    }

    /**
     * get the internal config for the given key.
     *
     * @param  string $key
     * @return string|integer|null
     */
    protected function getConfig($key)
    {
        return $this->config[$key] ?? null;
    }

    /**
     * Get estimated for total.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedTotal($readable = false)
    {
        return $this->times->getEstimated($readable);
    }

    /**
     * Get estimated per level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedPerLevel($level = '', $readable = false)
    {
        return $this->times_per_level->getEstimated($level, $readable);
    }

    /**
     * Get estimated per project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedPerProject($project_id = -1, $readable = false)
    {
        return $this->times_per_project->getEstimated($project_id, $readable);
    }

    /**
     * Get estimated per user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedPerUser($user_id = -1, $readable = false)
    {
        return $this->times_per_user->getEstimated($user_id, $readable);
    }

    /**
     * Get the estimated time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @return float
     */
    protected function getEstimatedTimeForTask(&$task)
    {
        if ($this->getNonTimeModeEnabled()) {
            if (array_key_exists('score', $task)) {
                $score = $task['score'];
            } else {
                $score = 0;
            }
            return $this->getNonTimeModeMinutes() * $score / 60;
        } else {
            return $task['time_estimated'];
        }
    }

    /**
     * Get the config value for the non-time-mode minutes,
     * but just make a call once; while the original value
     * is still -1.
     */
    protected function getNonTimeModeMinutes()
    {
        return $this->getConfig('non_time_mode_minutes');
    }

    /**
     * Get the bool if the non-time-mode is enabled or not.
     */
    protected function getNonTimeModeEnabled()
    {
        return $this->getNonTimeModeMinutes() > 0;
    }

    /**
     * Get the overtime times from the given
     * subtasks in the array.
     *
     * @param  array  $subtasks
     * @return float
     */
    protected function getOvertimeFromSubtasks($subtasks = [])
    {
        $out = 0.0;
        foreach ($subtasks as $subtask) {
            $tmp = $subtask['time_spent'] - $subtask['time_estimated'];
            $out += $tmp;
        }
        return $out;
    }

    /**
     * Init maybe and then return the overtime time
     * for the given task.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @return float
     */
    protected function getOvertimeForTask(&$task, $subtasks = [])
    {
        if ($this->getNonTimeModeEnabled()) {
            $full_hours = $this->getNonTimeModeMinutes() * $task['score'] / 60;
            $new_spent = $this->getSpentTimeForTask($task, $subtasks);
            $new_overtime = $new_spent - $full_hours;
            if ($new_spent <= $full_hours) {
                $new_overtime = 0;
            }
            if (!array_key_exists('time_overtime', $task)) {
                $task['time_overtime'] = $new_overtime;
            }
            return $new_overtime;
        } else {
            if (!array_key_exists('time_overtime', $task)) {
                $this->initOvertimeTimeForTask($task, $subtasks);
            }
            return $task['time_overtime'];
        }
    }

    /**
     * Get overtime for total.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeTotal($readable = false)
    {
        return $this->times->getOvertime($readable);
    }

    /**
     * Get overtime per level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimePerLevel($level = '', $readable = false)
    {
        return $this->times_per_level->getOvertime($level, $readable);
    }

    /**
     * Get overtime per project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimePerProject($project_id = -1, $readable = false)
    {
        return $this->times_per_project->getOvertime($project_id, $readable);
    }

    /**
     * Get overtime per user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimePerUser($user_id = -1, $readable = false)
    {
        return $this->times_per_user->getOvertime($user_id, $readable);
    }

    /**
     * Get a percentage float from the given string. E.g.
     * maybe it's a subtask with the title "30% todo".
     * In that case this function would return 0.3.
     * Otherwise it returns -1.
     *
     * @param  string $string
     * @return float
     */
    protected static function getPercentFromString($string = '')
    {
        if (!is_string($string) || $string === '') {
            return -1.0;
        }

        // search for a number followed by an optional whitespace and '%'
        if (preg_match('/([+-]?\d+(?:[.,]\d+)?)\s*%/u', $string, $m)) {
            // normalize decimal to a dot
            $num = str_replace(',', '.', $m[1]);
            // try to convert to a float
            if (is_numeric($num)) {
                $val = (float) $num;
                return $val / 100.0;
            }
        }
        return -1.0;
    }

    /**
     * Get the remaining times from the given
     * subtasks in the array.
     *
     * @param  array  $subtasks
     * @param  array   &$task    For modifying the open_subtasks key
     * @return float
     */
    protected function getRemainingFromSubtasks($subtasks = [], &$task = [])
    {
        $out = 0.0;
        foreach ($subtasks as $subtask) {
            // check if this subtask is open or not and add it, if it's open
            if ($subtask['status'] != 2) {
                $task['open_subtasks']++;
            }

            $tmp = self::remainingCalculation($subtask);

            // only add time as spending, as long as the spent time of the subtask
            // does not exceed the estimated time, so that in total
            // the remaining time will always represent the actual estimated
            // time throughout all subtasks
            if ($tmp > 0) {
                $out += $tmp;
            }
        }
        return $out;
    }

    /**
     * Get remaining for total.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingTotal($readable = false)
    {
        return $this->times->getRemaining($readable);
    }

    /**
     * Get remaining per level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingPerLevel($level = '', $readable = false)
    {
        return $this->times_per_level->getRemaining($level, $readable);
    }

    /**
     * Get remaining per project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingPerProject($project_id = -1, $readable = false)
    {
        return $this->times_per_project->getRemaining($project_id, $readable);
    }

    /**
     * Get remaining per user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingPerUser($user_id = -1, $readable = false)
    {
        return $this->times_per_user->getRemaining($user_id, $readable);
    }

    /**
     * Init maybe and then return the remaining time
     * for the given task.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @return float
     */
    protected function getRemainingTimeForTask(&$task, $subtasks = [])
    {
        if ($this->getNonTimeModeEnabled()) {

            // get data from the subtasks
            $time_override = 0;
            foreach ($subtasks as $subtask) {
                if (is_numeric($subtask['title'])) {
                    if ($subtask['title'] > 0) {
                        if ($subtask['status'] == 1) {
                            $time_override = (float) $subtask['title'] / 2;
                        } elseif ($subtask['status'] == 0) {
                            $time_override = (float) $subtask['title'];
                        } elseif ($subtask['status'] == 2) {
                            $time_override = 0;
                        }
                    } else {
                        $time_override = (float) $subtask['title'];
                    }
                }
            }

            // override is positive: it stands for remaining
            if ($time_override > 0) {
                $new_remaining = $time_override;

            // override is negative: it stans for spent
            } elseif ($time_override < 0) {
                $full_hours = $this->getNonTimeModeMinutes() * $task['score'] / 60;
                $new_remaining = $full_hours - ($time_override * -1);
                // i guess the first check was "enable non-time mode"
                // and this check is now to correct the newly calculated new_remaining
                // and cap it at 0 at least
                if ($new_remaining <= 0) {
                    $new_remaining = 0;
                }

            // no override
            } else {
                $new_remaining = (
                    $this->getEstimatedTimeForTask($task)
                    - $this->getSpentTimeForTask($task, $subtasks)
                );
            }

            // also set time_remaining key
            if (!array_key_exists('time_remaining', $task)) {
                $task['time_remaining'] = $new_remaining;
            }
            return $new_remaining;

        } else {
            if (!array_key_exists('time_remaining', $task)) {
                $this->initRemainingTimeForTask($task, $subtasks);
            }
            return $task['time_remaining'];
        }
    }

    /**
     * Get spent times from the tasks subtasks instead
     * of its own times. This is needed, if the task has
     * different times than its subtasks.
     *
     * @param  array &$task
     * @param  array $subtasks
     * @return float
     */
    protected function getSpentFromSubtasks(&$task, $subtasks = [])
    {
        $spent_time = 0.0;
        foreach ($subtasks as $subtask) {
            $spent_time += $subtask['time_spent'];
        }
        $task['time_spent'] = round($spent_time, 2);
        return $task['time_spent'];
    }

    /**
     * Get spent for total.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentTotal($readable = false)
    {
        return $this->times->getSpent($readable);
    }

    /**
     * Get spent per level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentPerLevel($level = '', $readable = false)
    {
        return $this->times_per_level->getSpent($level, $readable);
    }

    /**
     * Get spent per project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentPerProject($project_id = -1, $readable = false)
    {
        return $this->times_per_project->getSpent($project_id, $readable);
    }

    /**
     * Get spent per user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentPerUser($user_id = -1, $readable = false)
    {
        return $this->times_per_user->getSpent($user_id, $readable);
    }

    /**
     * Get the spent time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @return float
     */
    protected function getSpentTimeForTask(&$task, $subtasks = [])
    {
        // this has to be initialized if not existend; it's needed
        // at another point in the plugin. it counts the open subtasks
        // which have not the status 2
        if (!array_key_exists('open_subtasks', $task)) {
            $task['open_subtasks'] = 0;
        }
        if ($this->getNonTimeModeEnabled()) {

            // add 'percentage' to the subtasks keys
            self::extendSubtasksWithPercentage($subtasks);

            // now get the full hours and calculate how many subtasks
            // did work on that already, while the status also means
            // if 1 == half of its percentage is done on the full
            // hours and 2 == its percentage is done fully.
            $full_hours = $this->getNonTimeModeMinutes() * $task['score'] / 60;
            $worked = 0.0;
            $time_override = 0;
            $has_override = false;
            foreach ($subtasks as $subtask) {
                if (is_numeric($subtask['title'])) {
                    $has_override = true;
                    if ($subtask['title'] > 0) {
                        if ($subtask['status'] == 1) {
                            $time_override = (float) $subtask['title'] / 2;
                        } elseif ($subtask['status'] == 0) {
                            $time_override = (float) $subtask['title'];
                        } elseif ($subtask['status'] == 2) {
                            $time_override = 0;
                        }
                    } else {
                        $time_override = (float) $subtask['title'];
                    }
                } else {
                    // if this happens with the last subtask, it really should
                    // not be overwritten.
                    $has_override = false;
                }

                if ($subtask['status'] == 0) {
                    $task['open_subtasks']++;
                } elseif ($subtask['status'] == 1 ) {
                    $task['open_subtasks']++;
                    // a begun subtask should stand for 50% of its time already ...
                    $worked += $full_hours * ($subtask['percentage'] / 2);
                } elseif ($subtask['status'] == 2 ) {
                    $worked += $full_hours * $subtask['percentage'];
                }

            }

            if ($has_override) {
                // override is positive: it stands for remaining
                if ($time_override >= 0) {
                    $worked = $full_hours - $time_override;
                    if ($worked < 0) {
                        $worked = 0;
                    }

                // override is negative: it stans for spent
                } elseif ($time_override < 0) {
                    $worked = $full_hours - ($full_hours - ($time_override * -1));
                }
            }

            return $worked;

        } else {
            return $task['time_spent'];
        }
    }

    /**
     * Get the tasks array, which will hold the updated tasks on
     * the values with their id as the key to access them.
     *
     * A task id can be given directly and if the task exists
     * it will be returned directly. Otherwise an empty
     * array will be returned.
     *
     * @param integer $task_id
     * @return array
     */
    public function getTasks($task_id = -1)
    {
        if (array_key_exists($task_id, $this->tasks)) {
            return $this->tasks[$task_id];
        } else {
            return $this->tasks;
        }
    }

    /**
     * Get the internal tasks_per_level array. A level
     * can be defined as a parameter so that only that
     * level's task will be returned.
     *
     * @param string $level
     * @return array
     */
    public function getTasksPerLevel($level = '')
    {
        if (array_key_exists($level, $this->tasks_per_level)) {
            return $this->tasks_per_level[$level];
        } else {
            return $this->tasks_per_level;
        }
    }

    /**
     * Get the internal TimetaggerTranscriber. Maybe initialize it
     * first and get the Timetagger events accordingly. So this way
     * only if it really is needed, the API call will be made.
     *
     * @return TimetaggerTranscriber
     */
    protected function getTimetaggerTranscriber()
    {
        if (is_null($this->timetagger_transcriber)) {
            $this->initTimetagger();
        }
        return $this->timetagger_transcriber;
    }

    /**
     * The calculation logic for a task or subtask.
     * It varies on the state of the task / subtask.
     *
     * @param  array $task_or_subtask
     * @return float
     */
    protected static function remainingCalculation($task_or_subtask)
    {
        $done = (
            // it's a task
            isset($task_or_subtask['is_active']) && $task_or_subtask['is_active'] == 0
        ) || (
            // it's a subtask
            isset($task_or_subtask['status']) && $task_or_subtask['status'] == 2
        );

        // if the subtask is done or the tasks is closed,
        // yet the spent time is below the estimated time,
        // only use the lower spent time as the estimated time then
        if ($done && $task_or_subtask['time_spent'] < $task_or_subtask['time_estimated']) {
            $tmp_estimated = $task_or_subtask['time_spent'];
        } else {
            $tmp_estimated = $task_or_subtask['time_estimated'];
        }
        return $tmp_estimated - $task_or_subtask['time_spent'];
    }

    /**
     * Check if the config string matches the given swimlane string
     * and column string. E.g. the config can be something like:
     *
     *    "column_b [swimlane_a]"
     *
     * Now with the given swimlane "swimlane_a" and the given columne
     * "column_b" the check would be true.
     *
     * Example with the config string:
     *
     *    "column_a"
     *
     * Here the given parameter column "column_a" would be sufficient
     * already regardless of the swimlane, which is not given in brackets.
     *
     * @param  string $config_str
     * @param  string $swimlane
     * @param  string $column
     * @return boolean
     */
    protected static function swimlaneColumnCheck($config_str, $swimlane, $column)
    {
        //            1     2     3
        preg_match('/(.*)\[(.*)\](.*)/', $config_str, $re);

        // swimlane in bracktes given
        if ($re) {
            // column check
            if (trim($re[1]) == $column || trim($re[3]) == $column) {
                // and swimlane check
                if (trim($re[2]) == $swimlane) {
                    return true;
                }
            }

        // no swimlane in brackets given
        } else {
            // column check
            if (trim($config_str) == $column) {
                return true;
            }
        }
        return false;
    }
}
