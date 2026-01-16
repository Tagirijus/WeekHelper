<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Model\SortingLogic;
use Kanboard\Plugin\WeekHelper\Model\TaskInfoParser;


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
        'ignore_subtask_titles' => [],
        'non_time_mode_minutes' => 0,
        'sorting_logic' => '',
    ];

    /**
     * Array describing, which subtask IDs
     * are already found as "ignored".
     * It's a cache variable
     *
     * @var array
     **/
    var $subtask_ids_have_ignore_substring_in_title = [];

    /**
     * Array describing, which subtask IDs
     * are already found as "not ignored".
     * It's a cache variable
     *
     * @var array
     **/
    var $subtask_ids_do_not_have_ignore_substring_in_title = [];

    /**
     * The class attribute, holding the tasks per level.
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
     * Construct the instance with certain settings, if needed.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->initConfig($config);
    }

    /**
     * Function to add the calculation for each level in the
     * getTimesFromTasks() method.
     *
     * @param array &$level
     * @param string $level_key
     * @param array $levels
     * @param string $col_name
     * @param string $swim_name
     * @param array &$task
     * @param array $subtasks
     */
    protected function addTimesForLevel(
        &$level,
        $level_key,
        $levels,
        $col_name,
        $swim_name,
        &$task,
        $subtasks = []
    )
    {
        // check if the actual column name and swimlane name
        // are wanted for this level
        $exists = false;
        if (array_key_exists($level_key, $levels)) {
            $config = $levels[$level_key];
            foreach ($config as $col_swim) {
                if (self::swimlaneColumnCheck($col_swim, $swim_name, $col_name)) {
                    $exists = true;
                    break;
                }
            }
        }

        if ($exists) {
            // calculations
            $estimated = $this->getEstimatedTimeForTask($task);
            $spent = $this->getSpentTimeForTask($task, $subtasks);
            $remaining = $this->getRemainingTimeForTask($task, $subtasks);
            $overtime = $this->getOvertimeForTask($task, $subtasks);

            // dashbord: column times
            $level[$col_name]['estimated'] += $estimated;
            $level[$col_name]['spent'] += $spent;
            $level[$col_name]['remaining'] += $remaining;
            $level[$col_name]['overtime'] += $overtime;

            // level: total times
            $level['_total']['estimated'] += $estimated;
            $level['_total']['spent'] += $spent;
            $level['_total']['remaining'] += $remaining;
            $level['_total']['overtime'] += $overtime;
            self::extendHasTimes($level);

            // prepare native task and modify it, so that it has more data
            // which I need later. Then add it to the internal array of tasks
            // per level.
            $task['time_estimated'] = $estimated;
            $task['time_spent'] = $spent;
            $task['time_remaining'] = $remaining;
            $task['time_overtime'] = $overtime;
            array_push($this->tasks_per_level[$level_key], $task);
        }
    }

    /**
     * Check if the given array has any time above 0
     * like estimated, spent or remaining and if so
     * set the _has_times to true.
     *
     * @param  array &$arr
     */
    protected static function extendHasTimes(&$arr)
    {
        if (
            $arr['_total']['estimated'] > 0
            || $arr['_total']['spent'] > 0
            || $arr['_total']['remaining'] > 0
        ) {
            $arr['_has_times'] = true;
        }
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
    public static function extendSubtasksWithPercentage(&$subtasks)
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
     * This logic will handle the ignore feature
     * for subtasks calculation.
     *
     * use_ignore == 1
     *     This means that subtasktitles should be skipped,
     *     if they have a subtask title, which should
     *     be ignored according to the settings
     * use_ignore == 2
     *     This means that subtasktitles should be skipped,
     *     if they do not have a subtask title, which should
     *     be ignored according to the settings
     * use_ignore == 3 (or anything not 1|2)
     *     Means that the method returns false,
     *     thus nothing will be ignored. Will probably
     *     be used for "get all subtasks".
     *
     * @param  array $subtask
     * @param  integer $use_ignore
     * @return bool
     */
    public function ignoreLogic($subtask, $use_ignore)
    {
        if (
            ($use_ignore == 1 && $this->subtaskTitleHasIgnoreTitleSubString($subtask))
            ||
            ($use_ignore == 2 && !$this->subtaskTitleHasIgnoreTitleSubString($subtask))
        ) {
            return true;
        } else {
            return false;
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
        if (is_string($this->config['ignore_subtask_titles'])) {
            $this->config['ignore_subtask_titles'] = explode(',', $this->config['ignore_subtask_titles']);
        }
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
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    protected function initOvertimeTimeForTask(&$task = [], $subtasks = [], $use_ignore = 1)
    {
        $over_time = 0.0;
        if (isset($task['id'])) {

            // calculate remaining or overtime based on subtasks
            if (!empty($subtasks)) {
                $tmp = $this->getOvertimeFromSubtasks($subtasks, $use_ignore);

            // calculate remaining or overtime based only on task itself
            } else {
                $tmp = $task['time_spent'] - $task['time_estimated'];
            }

            $over_time = $tmp;

            // also add the remaining time, which otherwise
            // would generate an overtime, which is not wanted
            $over_time += $this->getRemainingTimeForTask($task, $use_ignore);
        }
        $task['time_overtime'] = round($over_time, 2);
        return $task['time_overtime'];
    }

    /**
     * Initialize the remaining time for the given task.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    protected function initRemainingTimeForTask(&$task = [], $subtasks = [], $use_ignore = 1)
    {
        $remaining_time = 0.0;
        if (isset($task['id'])) {
            $task['open_subtasks'] = 0;

            // calculate remaining or overtime based on subtasks
            if (!empty($subtasks)) {
                $tmp = $this->getRemainingFromSubtasks($subtasks, $use_ignore, $task);

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
     * get the internal config for the given key.
     *
     * @param  string $key
     * @return string|integer|null
     */
    public function getConfig($key)
    {
        return $this->config[$key] ?? null;
    }

    /**
     * Get estimated times from the tasks subtasks instead
     * of its own times. This is needed, if the task has
     * different times than its subtasks.
     *
     * @param  array &$task
     * @param  array $subtasks
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getEstimatedFromSubtasks(&$task, $subtasks = [], $use_ignore = 1)
    {
        $estimated_time = 0.0;
        foreach ($subtasks as $subtask) {
            if ($this->ignoreLogic($subtask, $use_ignore)) {
                continue;
            }
            $estimated_time += $subtask['time_estimated'];
        }
        $task['time_estimated'] = round($estimated_time, 2);
        return $task['time_estimated'];
    }

    /**
     * Get the estimated time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @return float
     */
    public function getEstimatedTimeForTask(&$task)
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
     * Init and/or ge the ignored subtask titles.
     *
     * @return array
     */
    public function getIgnoredSubtaskTitles()
    {
        return $this->getConfig('ignore_subtask_titles');
    }

    /**
     * Get the config value for the non-time-mode minutes,
     * but just make a call once; while the original value
     * is still -1.
     */
    public function getNonTimeModeMinutes()
    {
        return $this->getConfig('non_time_mode_minutes');
    }

    /**
     * Get the bool if the non-time-mode is enabled or not.
     */
    public function getNonTimeModeEnabled()
    {
        return $this->getNonTimeModeMinutes() > 0;
    }

    /**
     * Get the overtime times from the given
     * subtasks in the array.
     *
     * @param  array  $subtasks
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    protected function getOvertimeFromSubtasks($subtasks = [], $use_ignore = 1)
    {
        $out = 0.0;
        foreach ($subtasks as $subtask) {
            if ($this->ignoreLogic($subtask, $use_ignore)) {
                continue;
            }

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
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getOvertimeForTask(&$task, $subtasks = [], $use_ignore = 1)
    {
        if ($this->getNonTimeModeEnabled()) {
            $full_hours = $this->getNonTimeModeMinutes() * $task['score'] / 60;
            $new_spent = $this->getSpentTimeForTask($task, $subtasks, $use_ignore);
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
                $this->initOvertimeTimeForTask($task, $subtasks, $use_ignore);
            }
            return $task['time_overtime'];
        }
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
    public static function getPercentFromString($string = '')
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
     * @param  integer   $use_ignore
     *         1: get only non-ignored subtasks times
     *         2: get only ignored subtask times
     *         3: get all times
     * @param  array   &$task    For modifying the open_subtasks key
     * @return float
     */
    protected function getRemainingFromSubtasks($subtasks = [], $use_ignore = 1, &$task = [])
    {
        $out = 0.0;
        foreach ($subtasks as $subtask) {
            if ($this->ignoreLogic($subtask, $use_ignore)) {
                continue;
            }

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
     * Init maybe and then return the remaining time
     * for the given task.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getRemainingTimeForTask(&$task, $subtasks = [], $use_ignore = 1)
    {
        if ($this->getNonTimeModeEnabled()) {

            // get data from the subtasks
            $time_override = 0;
            foreach ($subtasks as $subtask) {
                if ($this->ignoreLogic($subtask, $use_ignore)) {
                    continue;
                }
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
                $this->initRemainingTimeForTask($task, $subtasks, $use_ignore);
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
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getSpentFromSubtasks(&$task, $subtasks = [], $use_ignore = 1)
    {
        $spent_time = 0.0;
        foreach ($subtasks as $subtask) {
            if ($this->ignoreLogic($subtask, $use_ignore)) {
                continue;
            }
            $spent_time += $subtask['time_spent'];
        }
        $task['time_spent'] = round($spent_time, 2);
        return $task['time_spent'];
    }

    /**
     * Get the spent time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @param  array  $subtasks
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getSpentTimeForTask(&$task, $subtasks = [], $use_ignore = 1)
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
                if ($this->ignoreLogic($subtask, $use_ignore)) {
                    continue;
                }
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
     * Get the internal tasks_per_level array.
     *
     * @return array
     */
    public function getTasksPerLevel()
    {
        return $this->tasks_per_level;
    }

    /**
     * Get the estimated and spent times in the columns for
     * the total (all) and the levels (level_1, level_2, ...).
     *
     * New since v1.2.0: remaining
     * New since v1.13.0: overtime
     *
     *
     * Array output:
     *
     * [
     *     'all' => [
     *         '_total' => [
     *             'estimated' => 8,
     *             'spent' => 6.5,
     *             'remaining' => 1.5,
     *             'overtime' => 0
     *         ],
     *         'column b' => [
     *             'estimated' => 5,
     *             'spent' => 4.5,
     *             'remaining' => 0.5,
     *             'overtime' => 0
     *         ]
     *     ],
     *     'level_1' => [
     *         '_total' => [
     *             'estimated' => 7,
     *             'spent' => 5.5,
     *             'remaining' => 1.5,
     *             'overtime' => 0
     *         ],
     *         'column a' => [
     *             'estimated' => 2,
     *             'spent' => 3,
     *             'remaining' => 0,
     *             'overtime' => 1
     *         ]
     *     ],
     *     'level_2' => ...
     * ]
     *
     * @param  array &$tasks
     * @param  array $subtasks_by_task_id
     * @return array
     */
    public function getTimesFromTasks(&$tasks, $subtasks_by_task_id = [])
    {
        // a task can have certain values given in the description text, which
        // can be parsed into task array keys. e.g. "project_type" can be overwritten
        // here, etc.
        foreach ($tasks as &$task) {
            TaskInfoParser::extendTask($task);
        }
        unset($task);

        $levels_columns = [
            'level_1' => $this->getConfig('level_1_columns'),
            'level_2' => $this->getConfig('level_2_columns'),
            'level_3' => $this->getConfig('level_3_columns'),
            'level_4' => $this->getConfig('level_4_columns')
        ];

        $all = [
            '_has_times' => false,
            '_total' => [
                'estimated' => 0,
                'spent' => 0,
                'remaining' => 0,
                'overtime' => 0
            ]
        ];
        $level_1 = $all;
        $level_2 = $all;
        $level_3 = $all;
        $level_4 = $all;
        $col_name = 'null';

        foreach ($tasks as &$task) {
            // get column name and swimlane
            $col_name = $task['column_name'];
            $swim_name = $task['swimlane_name'];

            // subtasks getting
            $subtasks = $subtasks_by_task_id[$task['id']] ?? [];

            // set new column key in the time calc arrays
            self::setTimeCalcKey($all, $col_name);
            self::setTimeCalcKey($level_1, $col_name);
            self::setTimeCalcKey($level_2, $col_name);
            self::setTimeCalcKey($level_3, $col_name);
            self::setTimeCalcKey($level_4, $col_name);

            // all: column times
            $all[$col_name]['estimated'] += $this->getEstimatedTimeForTask($task);
            $all[$col_name]['spent'] += $this->getSpentTimeForTask($task, $subtasks);
            $all[$col_name]['remaining'] += $this->getRemainingTimeForTask($task, $subtasks);
            $all[$col_name]['overtime'] += $this->getOvertimeForTask($task, $subtasks);

            // all: total times
            $all['_total']['estimated'] += $this->getEstimatedTimeForTask($task);
            $all['_total']['spent'] += $this->getSpentTimeForTask($task, $subtasks);
            $all['_total']['remaining'] += $this->getRemainingTimeForTask($task, $subtasks);
            $all['_total']['overtime'] += $this->getOvertimeForTask($task, $subtasks);
            self::extendHasTimes($all);


            // level times
            $this->addTimesForLevel($level_1, 'level_1', $levels_columns, $col_name, $swim_name, $task, $subtasks);
            $this->addTimesForLevel($level_2, 'level_2', $levels_columns, $col_name, $swim_name, $task, $subtasks);
            $this->addTimesForLevel($level_3, 'level_3', $levels_columns, $col_name, $swim_name, $task, $subtasks);
            $this->addTimesForLevel($level_4, 'level_4', $levels_columns, $col_name, $swim_name, $task, $subtasks);
        }
        unset($task);

        // update the tasks on the levels with correct sorting
        // and TimetaggerTranscriber overwrite for the times_spent
        $this->updateTasksPerLevel();

        return [
            'all' => $all,
            'level_1' => $level_1,
            'level_2' => $level_2,
            'level_3' => $level_3,
            'level_4' => $level_4
        ];
    }

    /**
     * The calculation logic for a task or subtask.
     * It varies on the state of the task / subtask.
     *
     * @param  array $task_or_subtask
     * @return float
     */
    public static function remainingCalculation($task_or_subtask)
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
     * Check if the array key exists and add it, if not.
     *
     * @param array &$arr
     * @param string $col_name
     */
    protected static function setTimeCalcKey(&$arr, $col_name)
    {
        if (!isset($arr[$col_name])) {
            $arr[$col_name] = ['estimated' => 0, 'spent' => 0, 'remaining' => 0, 'overtime' => 0];
        }
    }

    /**
     * Checks, if the given subtask has a substring in its title,
     * which exists in the configs "ignore subtasks" setting.
     *
     * @param  array $subtask
     * @return bool
     */
    public function subtaskTitleHasIgnoreTitleSubString($subtask)
    {
        $subtask_is_ignored = in_array($subtask['id'], $this->subtask_ids_have_ignore_substring_in_title);
        // return already to save some premature performance, since
        // it means that the subtask was checked already
        if ($subtask_is_ignored) {return $subtask_is_ignored;}
        $subtask_is_not_ignored = in_array($subtask['id'], $this->subtask_ids_do_not_have_ignore_substring_in_title);
        $subtask_was_checked = $subtask_is_ignored || $subtask_is_not_ignored;

        // first check, if the subtask was checked already
        if ($subtask_was_checked) {
            return $subtask_is_ignored;

        // or check it new
        } else {
            $found = false;
            foreach ($this->getIgnoredSubtaskTitles() as $ignore_substring) {
                if (strpos($subtask['title'], $ignore_substring) !== false) {
                    $found = true;
                }
            }
            if ($found) {
                $this->subtask_ids_have_ignore_substring_in_title[] = $subtask['id'];
            } else {
                $this->subtask_ids_do_not_have_ignore_substring_in_title[] = $subtask['id'];
            }
            return $found;
        }
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
    public static function swimlaneColumnCheck($config_str, $swimlane, $column)
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

    /**
     * Update the internal tasks_per_level, which will contain:
     *
     * 1. The tasks there shall be sorted according to the wanted
     * sortgin logic.
     *
     * 2. The level for which TimetaggerTranscriber should overwrite
     * the times_spent values of the tasks should be updated.
     */
    public function updateTasksPerLevel()
    {
        foreach ($this->tasks_per_level as &$tasks) {
            $tasks = SortingLogic::sortTasks(
                $tasks,
                $this->getConfig('sorting_logic')
            );
        }
        unset($tasks);

        // TODO
        // TimetaggerTranscriber magic here ...
    }
}
