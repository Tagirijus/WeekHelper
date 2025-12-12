<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;
use Kanboard\Model\TaskModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\SubtaskModel;
use Kanboard\Core\Paginator;
use Kanboard\Filter\TaskProjectsFilter;


class HoursViewHelper extends Base
{
    /**
     * Subtasks cache-variable:
     * [task_id => subtask_array]
     *
     * @var array
     **/
    var $subtasks = [];

    /**
     * Array describing which task already
     * got ALL subtasks added to the subtasks
     * cache-variable.
     *
     * @var array
     **/
    var $task_got_subtasks = [];

    /**
     * Array describing, which subtask titles
     * should be ignoed during calculation.
     * It's a cache variable
     *
     * @var array
     **/
    var $ignore_subtask_titles = null;

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
     * The minutes which stand for 1 complexity of a task.
     * If 0 the non-time-mode is disabled. The value is -1
     * for this class to know it has to be initialized.
     *
     * @var integer
     **/
    var $non_time_mode_minutes = -1;

    /**
     * The bool if the non-time-mode is enabled or not.
     *
     * @var boolean
     **/
    var $non_time_mode_enabled = false;

    /**
     * Init and/or ge the ignored subtask titles.
     *
     * @return array
     */
    public function getIgnoredSubtaskTitles()
    {
        if (is_null($this->ignore_subtask_titles)) {
            $this->ignore_subtask_titles = explode(',', $this->configModel->get('hoursview_ignore_subtask_titles', ''));
        }
        return $this->ignore_subtask_titles;
    }

    /**
     * Check if subtasks for task exist and return
     * the array, or otherwise fetch it from
     * the DB.
     *
     * @param  integer $taskId
     * @return array
     */
    public function getSubtasksByTaskId($taskId)
    {
        if (!array_key_exists($taskId, $this->subtasks)) {
            $this->subtasks[$taskId] = $this->subtaskModel->getAll($taskId);
            $this->task_got_subtasks[] = $taskId;
        }
        return $this->subtasks[$taskId];
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
     * @param  array $tasks
     * @return array
     */
    public function getTimesFromTasks($tasks)
    {
        $levels_columns = [
            'level_1' => explode(',', $this->configModel->get('hoursview_level_1_columns', '')),
            'level_2' => explode(',', $this->configModel->get('hoursview_level_2_columns', '')),
            'level_3' => explode(',', $this->configModel->get('hoursview_level_3_columns', '')),
            'level_4' => explode(',', $this->configModel->get('hoursview_level_4_columns', ''))
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

        foreach ($tasks as $task) {
            // get column name and swimlane
            $col_name = $task['column_name'];
            $swim_name = $task['swimlane_name'];

            // set new column key in the time calc arrays
            $this->setTimeCalcKey($all, $col_name);
            $this->setTimeCalcKey($level_1, $col_name);
            $this->setTimeCalcKey($level_2, $col_name);
            $this->setTimeCalcKey($level_3, $col_name);
            $this->setTimeCalcKey($level_4, $col_name);

            // all: column times
            $all[$col_name]['estimated'] += $this->getEstimatedTimeForTask($task);
            $all[$col_name]['spent'] += $this->getSpentTimeForTask($task);
            $all[$col_name]['remaining'] += $this->getRemainingTimeForTask($task);
            $all[$col_name]['overtime'] += $this->getOvertimeForTask($task);

            // all: total times
            $all['_total']['estimated'] += $this->getEstimatedTimeForTask($task);
            $all['_total']['spent'] += $this->getSpentTimeForTask($task);
            $all['_total']['remaining'] += $this->getRemainingTimeForTask($task);
            $all['_total']['overtime'] += $this->getOvertimeForTask($task);
            $this->modifyHasTimes($all);


            // level times
            $this->addTimesForLevel($level_1, 'level_1', $levels_columns, $col_name, $swim_name, $task);
            $this->addTimesForLevel($level_2, 'level_2', $levels_columns, $col_name, $swim_name, $task);
            $this->addTimesForLevel($level_3, 'level_3', $levels_columns, $col_name, $swim_name, $task);
            $this->addTimesForLevel($level_4, 'level_4', $levels_columns, $col_name, $swim_name, $task);
        }

        return [
            'all' => $all,
            'level_1' => $level_1,
            'level_2' => $level_2,
            'level_3' => $level_3,
            'level_4' => $level_4
        ];
    }

    /**
     * Check if the given array has any time above 0
     * like estimated, spent or remaining and if so
     * set the _has_times to true.
     *
     * @param  array &$arr
     */
    protected function modifyHasTimes(&$arr)
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
     * Check if the array key exists and add it, if not.
     *
     * @param array &$arr
     * @param string $col_name
     */
    protected function setTimeCalcKey(&$arr, $col_name)
    {
        if (!isset($arr[$col_name])) {
            $arr[$col_name] = ['estimated' => 0, 'spent' => 0, 'remaining' => 0, 'overtime' => 0];
        }
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
     * @param array $task
     */
    protected function addTimesForLevel(&$level, $level_key, $levels, $col_name, $swim_name, $task)
    {
        // check if the actual column name and swimlane name
        // are wanted for this level
        $exists = false;
        if (array_key_exists($level_key, $levels)) {
            $config = $levels[$level_key];
            foreach ($config as $col_swim) {
                //            1     2     3
                preg_match('/(.*)\[(.*)\](.*)/', $col_swim, $re);

                // swimlane in bracktes given
                if ($re) {
                    // column check
                    if (trim($re[1]) == $col_name || trim($re[3]) == $col_name) {
                        // and swimlane check
                        if (trim($re[2]) == $swim_name) {
                            $exists = true;
                        }
                    }

                // no swimlane in brackets given
                } else {
                    // column check
                    if (trim($col_swim) == $col_name) {
                        $exists = true;
                    }
                }
            }
        }

        if ($exists) {
            // dashbord: column times
            $level[$col_name]['estimated'] += $this->getEstimatedTimeForTask($task);
            $level[$col_name]['spent'] += $this->getSpentTimeForTask($task);
            $level[$col_name]['remaining'] += $this->getRemainingTimeForTask($task);
            $level[$col_name]['overtime'] += $this->getOvertimeForTask($task);

            // level: total times
            $level['_total']['estimated'] += $this->getEstimatedTimeForTask($task);
            $level['_total']['spent'] += $this->getSpentTimeForTask($task);
            $level['_total']['remaining'] += $this->getRemainingTimeForTask($task);
            $level['_total']['overtime'] += $this->getOvertimeForTask($task);
            $this->modifyHasTimes($level);
        }
    }

    /**
     * Get the estimated and spent times in the columns for
     * all tasks with a given project id.
     *
     * This method wraps basically the getTimesFromTasks()
     * method, but with a given project id to get the
     * linked tasks.
     *
     * @param  integer $projectId
     * @return array
     */
    public function getTimesByProjectId($projectId)
    {
        $tasks = $this->getOpenTasksByProjectId($projectId);

        return $this->getTimesFromTasks($tasks);
    }

    /**
     * Get an array with the calculated times for
     * the given column array.
     *
     * Array output:
     *
     * [
     *     'estimated' => 2,
     *     'spent' => 1,
     *     'remaining' => 1,
     *     'overtime' => 0
     * ]
     *
     * @param  array $column
     * @return array
     */
    public function getTimesForColumn($column)
    {
        $out = ['estimated' => 0, 'spent' => 0, 'remaining' => 0, 'overtime' => 0];
        if (isset($column['tasks'])) {
            foreach ($column['tasks'] as $task) {
                $out['estimated'] += $this->getEstimatedTimeForTask($task);
                $out['spent'] += $this->getSpentTimeForTask($task);
                $out['remaining'] += $this->getRemainingTimeForTask($task);
                $out['overtime'] += $this->getOvertimeForTask($task);
            }
        }
        return $out;
    }

    /**
     * Get the config value for the non-time-mode minutes,
     * but just make a call once; while the original value
     * is still -1.
     */
    public function getNonTimeModeMinutes()
    {
        if ($this->non_time_mode_minutes == -1) {
            $this->non_time_mode_minutes = $this->configModel->get('hoursview_non_time_mode_minutes', 0);
        }
        return $this->non_time_mode_minutes;
    }

    /**
     * Get the bool if the non-time-mode is enabled or not.
     */
    public function getNonTimeModeEnabled()
    {
        if ($this->non_time_mode_minutes == -1) {
            $this->non_time_mode_minutes = $this->configModel->get('hoursview_non_time_mode_minutes', 0);
        }
        return $this->non_time_mode_minutes > 0;
    }

    /**
     * Basically some kind of wrapper function for getting
     * the array with all the columns for the project.
     *
     * Thus here the array-keys are the column id.
     *
     * @param  integer $projectId
     * @return array
     */
    protected function getColumnsByProjectId($projectId)
    {
        $out = [];
        $columns = $this->columnModel->getAll($projectId);
        foreach ($columns as $column) {
            $out[$column['id']] = $column;
        }
        return $out;
    }

    /**
     * Basically some kind of wrapper function for getting
     * the array with all the tasks for the project.
     *
     * @param  integer $projectId
     * @return array
     */
    protected function getOpenTasksByProjectId($projectId)
    {
        $project = $this->projectModel->getById($projectId);

        // this is not needed anymore, since I just want to get open
        // tasks anyway, which would get "status:open" here anyway.
        // $search = $this->helper->projectHeader->getSearchQuery($project);

        $query = $this->taskFinderModel->getExtendedQuery()
            ->eq(TaskModel::TABLE.'.project_id', $projectId);

        $builder = $this->taskLexer;
        $builder->withQuery($query);
        return $builder->build('status:open')->toArray();
    }

    /**
     * This one gets all tasks for the user and their
     * respecting times.
     *
     * Array output:
     *
     * [
     *     'estimated' => 2,
     *     'spent' => 1
     * ]
     *
     * @param  integer $userId
     * @return array
     */
    public function getTimesByUserId($userId)
    {
        $tasks = $this->taskFinderModel->getExtendedQuery()
            ->beginOr()
            ->eq(TaskModel::TABLE.'.owner_id', $userId)
            ->addCondition(TaskModel::TABLE.".id IN (SELECT task_id FROM ".SubtaskModel::TABLE." WHERE ".SubtaskModel::TABLE.".user_id='$userId')")
            ->closeOr()
            ->eq(TaskModel::TABLE.'.is_active', TaskModel::STATUS_OPEN)
            ->eq(ProjectModel::TABLE.'.is_active', ProjectModel::ACTIVE)
            ->findAll();

        return $this->getTimesFromTasks($tasks);
    }

    /**
     * Get level captions from the config.
     *
     * @return array
     */
    public function getLevelCaptions()
    {
        $levels_captions = [
            'level_1' => $this->configModel->get('hoursview_level_1_caption', ''),
            'level_2' => $this->configModel->get('hoursview_level_2_caption', ''),
            'level_3' => $this->configModel->get('hoursview_level_3_caption', ''),
            'level_4' => $this->configModel->get('hoursview_level_4_caption', ''),
            'all' => $this->configModel->get('hoursview_all_caption', '')
        ];
        return $levels_captions;
    }

    /**
     * Represent the given float as a proper time string.
     *
     * @param  float $time
     * @return string
     */
    public function floatToHHMM($time)
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
     * Get configuration for plugin as array.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'title' => t('HoursView') . ' &gt; ' . t('Settings'),
            'non_time_mode_minutes' => $this->configModel->get('hoursview_non_time_mode_minutes', 0),
            'level_1_columns' => $this->configModel->get('hoursview_level_1_columns', ''),
            'level_2_columns' => $this->configModel->get('hoursview_level_2_columns', ''),
            'level_3_columns' => $this->configModel->get('hoursview_level_3_columns', ''),
            'level_4_columns' => $this->configModel->get('hoursview_level_4_columns', ''),
            'level_1_caption' => $this->configModel->get('hoursview_level_1_caption', ''),
            'level_2_caption' => $this->configModel->get('hoursview_level_2_caption', ''),
            'level_3_caption' => $this->configModel->get('hoursview_level_3_caption', ''),
            'level_4_caption' => $this->configModel->get('hoursview_level_4_caption', ''),
            'all_caption' => $this->configModel->get('hoursview_all_caption', ''),
            'ignore_subtask_titles' => $this->configModel->get('hoursview_ignore_subtask_titles', ''),
            'progressbar_enabled' => $this->configModel->get('hoursview_progressbar_enabled', 1),
            'progressbar_opacity' => $this->configModel->get('hoursview_progressbar_opacity', 1),
            'progressbar_0_opacity' => $this->configModel->get('hoursview_progressbar_0_opacity', 0.15),
            'progress_home_project_level' => $this->configModel->get('hoursview_progress_home_project_level', 'all'),
            'hide_0hours_projects_enabled' => $this->configModel->get('hoursview_hide_0hours_projects_enabled', 0),
            'block_hours' => $this->configModel->get('hoursview_block_hours', 0),
            'dashboard_link_level_1' => $this->configModel->get('hoursview_dashboard_link_level_1', 0),
            'dashboard_link_level_2' => $this->configModel->get('hoursview_dashboard_link_level_2', 0),
            'dashboard_link_level_3' => $this->configModel->get('hoursview_dashboard_link_level_3', 0),
            'dashboard_link_level_4' => $this->configModel->get('hoursview_dashboard_link_level_4', 0),
            'dashboard_link_level_all' => $this->configModel->get('hoursview_dashboard_link_level_all', 0),
        ];
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
    public function getPercentFromString($string = '')
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
    public function extendSubtasksWithPercentage(&$subtasks)
    {
        $countWithout = 0;
        $percentRemaining = 1.0;

        // first run: parse, set known percentages, count unknowns
        foreach ($subtasks as $k => $s) {
            $p = $this->getPercentFromString($s['title']);
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
     * Get the spent time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getSpentTimeForTask(&$task, $use_ignore = 1)
    {
        // this has to be initialized if not existend; it's needed
        // at another point in the plugin. it counts the open subtasks
        // which have not the status 2
        if (!array_key_exists('open_subtasks', $task)) {
            $task['open_subtasks'] = 0;
        }
        if ($this->getNonTimeModeEnabled()) {
            $subtasks = $this->getSubtasksByTaskId($task['id']);

            // add 'percentage' to the subtasks keys
            $this->extendSubtasksWithPercentage($subtasks);

            // now get the full hours and calculate how many subtasks
            // did work on that already, while the status also means
            // if 1 == half of its percentage is done on the full
            // hours and 2 == its percentage is done fully.
            $full_hours = $this->getNonTimeModeMinutes() * $task['score'] / 60;
            $worked = 0.0;
            foreach ($subtasks as $subtask) {
                if ($this->ignoreLogic($subtask, $use_ignore)) {
                    continue;
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

            return $worked;

        } else {
            return $task['time_spent'];
        }
    }

    /**
     * Init maybe and then return the remaining time
     * for the given task.
     *
     * @param  array  &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getRemainingTimeForTask(&$task, $use_ignore = 1)
    {
        if ($this->getNonTimeModeEnabled()) {
            $subtasks = $this->getSubtasksByTaskId($task['id']);

            // get data from the subtasks
            $last_remaining_override = -1;
            foreach ($subtasks as $subtask) {
                if ($this->ignoreLogic($subtask, $use_ignore)) {
                    continue;
                }
                if (is_numeric($subtask['title'])) {
                    if ($subtask['status'] == 1) {
                        $last_remaining_override = (float) $subtask['title'] / 2;
                    } elseif ($subtask['status'] == 0) {
                        $last_remaining_override = (float) $subtask['title'];
                    } elseif ($subtask['status'] == 2) {
                        $last_remaining_override = 0;
                    }
                }
            }
            if ($last_remaining_override != -1) {
                return $last_remaining_override;
            } else {
                return $this->getEstimatedTimeForTask($task) - $this->getSpentTimeForTask($task);
            }
        } else {
            if (!array_key_exists('time_remaining', $task)) {
                $this->initRemainingTimeForTask($task, $use_ignore);
            }
            return $task['time_remaining'];
        }
    }

    /**
     * Initialize the remaining time for the given task.
     *
     * @param  array  &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    protected function initRemainingTimeForTask(&$task = [], $use_ignore = 1)
    {
        $remaining_time = 0.0;
        if (isset($task['id'])) {
            $subtasks = $this->getSubtasksByTaskId($task['id']);
            $task['open_subtasks'] = 0;

            // calculate remaining or overtime based on subtasks
            if (!empty($subtasks)) {
                $tmp = $this->getRemainingFromSubtasks($subtasks, $use_ignore, $task);

            // calculate remaining or overtime based only on task itself
            } else {
                $tmp = $this->remainingCalculation($task);
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
     * This logic will handle the ignore feature
     * for subtasks caculation.
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

            $tmp = $this->remainingCalculation($subtask);

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
     * The calculation logic for a task or subtask.
     * It varies on the state of the task / subtask.
     *
     * @param  array $task_or_subtask
     * @return float
     */
    public function remainingCalculation($task_or_subtask)
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
     * Init maybe and then return the overtime time
     * for the given task.
     *
     * @param  array  &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getOvertimeForTask(&$task, $use_ignore = 1)
    {
        if ($this->getNonTimeModeEnabled()) {
            return 0;
        } else {
            if (!array_key_exists('time_overtime', $task)) {
                $this->initOvertimeTimeForTask($task, $use_ignore);
            }
            return $task['time_overtime'];
        }
    }

    /**
     * Get the overtime with the correct sign to
     * show in the header.
     *
     * E.g. either there was overtime; then it will
     * be shown as "time_estimated" + "overtime".
     *
     * If you worked faster it's "time_estimated" - "overtime".
     *
     * @param  float $overtime
     * @return string
     */
    public function getOvertimeForTaskAsString($overtime)
    {
        if ($overtime > 0) {
            $prefix = '+ ';
        } else {
            $prefix = '- ';
        }
        return $prefix . $this->floatToHHMM(abs($overtime)) . 'h';
    }

    /**
     * Initialize the overtime for the given task.
     *
     * @param  array  &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    protected function initOvertimeTimeForTask(&$task = [], $use_ignore = 1)
    {
        $over_time = 0.0;
        if (isset($task['id'])) {
            $subtasks = $this->getSubtasksByTaskId($task['id']);

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
     * With the consideration of the subtask status, a subtask
     * might be done earlier than estimated. This way there might be
     * an available overhead-time. Or even vice versa and there
     * is less time left, since I mis-estimated the times.
     *
     * In either way this method is for calculating the difference.
     *
     * @param  array &$task
     * @return float
     */
    public function getSlowerOrFasterThanEstimatedForTask(&$task)
    {
        $remaining = $this->getRemainingTimeForTask($task);
        $estimated = $this->getEstimatedTimeForTask($task);
        $spent = $this->getSpentTimeForTask($task);
        return $estimated - $spent - $remaining;
    }

    /**
     * Wrapper for the getSlowerOrFasterThanEstimatedForTask()
     * method to render the ouput sign.
     *
     * @param  array &$task
     * @return string
     */
    public function getSlowerOrFasterSign(&$task)
    {
        $slowerOrFaster = $this->getSlowerOrFasterThanEstimatedForTask($task);
        if ($slowerOrFaster > 0) {
            $out = '>>';
        } else {
            $out = '<<';
        }
        // how it was before:
        // $out .= $this->floatToHHMM(abs($slowerOrFaster)) . ' h';
        return $out;
    }

    /**
     * Calculate the percent with the given task.
     * Use the times for this.
     *
     * Future idea:
     *    Maybe use the amount of subtasks, if no
     *    estimstd times exist at all.
     *
     * @param  array &$task
     * @param  bool $overtime
     * @return integer
     */
    public function getPercentForTask(&$task, $overtime = false)
    {
        $out = 0;

        // Calculate percentage from given times, while considering
        // the possible subtask times. These can vary, since
        // done subtasks will use the spent time as their
        // estimated time, if they are done already. This would
        // mean less (or sometimes more!) estimated overall time
        // after all. To do so I won't simply calculate
        // "spent / estimated" for the percentage, but rather:
        //      "(estimated - remaining) / estimated"
        //
        // Yet I can only do so, if the given $task is really a
        // task array with an 'id'; otherwise just do the normal
        // calculation instead ...
        if ($this->getEstimatedTimeForTask($task) != 0) {
            $estimated = $this->getEstimatedTimeForTask($task);
            $remaining = $this->getRemainingTimeForTask($task);
            $spent = $estimated - $remaining;

            if ($estimated != 0) {
                $out = round($spent / $estimated * 100, 0);
            } else {
                $out = 100;
            }
        }

        // consider overtime
        if ($overtime) {
            if ($out > 100) {
                $out = $out - 100;
            } else {
                $out = 0;
            }
        }

        // prevent negative percentages, which
        // might occur due to rounding issues,
        // I guess? - monkey patch!
        if ($out <= 0) {
            $out = 0;
        }

        return $out;
    }

    /**
     * Get percentage for a task according to its
     * spent time and estimated time (or in the future
     * maybe depending on the subtasks) and render
     * it as a string with percentage symbol.
     *
     * Also there is the option to add additional info like
     * the overtime.
     *
     * @param  array &$task
     * @param  string $symbol
     * @param  bool $overtime
     * @return string
     */
    public function getPercentForTaskAsString(&$task, $symbol = '%', $overtime = false)
    {
        $percent_over = $this->getPercentForTask($task, true);

        if ($overtime && $percent_over > 0) {
            $out = '100' . $symbol . ' (+' . $this->getPercentForTask($task, true) . $symbol . ')';
        } else {
            $out = $this->getPercentForTask($task, false) . $symbol;
        }

        return $out;
    }

    /**
     * Generate additional task progress bar CSS
     * depending on the given percentage.
     *
     * Output will be the class in
     *     week-helper.css
     *
     * @param  integer $percent
     * @param  array   $task      To check if there are open subtasks or not
     * @return string
     */
    public function getPercentCSSClass($percent = 0, $task = [])
    {
        if ($percent >= 50 && $percent < 75) {
            return 'progress-color-50';
        } elseif ($percent >= 75 && $percent < 100) {
            return 'progress-color-75';
        } elseif ($percent >= 100 && $task['open_subtasks'] == 0) {
            return 'progress-color-100';
        } elseif ($percent >= 100 && $task['open_subtasks'] != 0) {
            return 'progress-color-100-undone';
        } else {
            return 'progress-color';
        }
    }

    /**
     * According to the wanted levels from the config,
     * sum up all the respecting time values for e.g.
     * the "project_times_summary_single.php".
     *
     * @param  array $times
     * @return array
     */
    public function prepareProjectTimesWithConfig($times)
    {
        $out = [
            'estimated' => 0,
            'spent' => 0,
            'remaining' => 0,
            'overtime' => 0,
        ];

        // Get levels from config
        $levels = explode(',', $this->configModel->get('hoursview_progress_home_project_level', 'all'));

        // iter through levels, while checking if they exist in the $times as key
        foreach ($levels as $level) {
            $level_trimmed = trim($level);
            if (array_key_exists($level, $times)) {
                $out['estimated'] += $times[$level]['_total']['estimated'];
                $out['spent'] += $times[$level]['_total']['spent'];
                $out['remaining'] += $times[$level]['_total']['remaining'];
                $out['overtime'] += $times[$level]['_total']['overtime'];
            }
        }

        return $out;
    }

    /**
     * Get all tasks from the the search URI,
     * ignoring the pagination.
     *
     * ATTENTION:
     *     This method might be overcomplicated, since
     *     it uses the Paginator() class to get the
     *     tasks. There might be a smarter way to
     *     query the tasks with the given search string,
     *     but I had no time to dive deeper into the
     *     Kanboard framework to find out how. Yet
     *     the PicoDB thing seems quite nice, though.
     *     So maybe some day I might improve this
     *     method or make it more logical, since it
     *     is not that clever (to me) to use the
     *     paginator for such a query. I do not
     *     need "other pages" after all.
     *
     *     This method is only for getting ALL tasks
     *     with the given search string.
     *
     * @return array
     */
    public function getAllTasksFromSearch()
    {
        $out = [];
        $projects = $this->projectUserRoleModel->getActiveProjectsByUser($this->userSession->getId());
        $search = urldecode($this->request->getStringParam('search'));
        if ($search !== '' && ! empty($projects)) {
            $paginator = new Paginator($this->container);
            $paginator
                ->setMax(999999)
                ->setFormatter($this->taskListFormatter)
                ->setQuery($this->taskLexer
                    ->build($search)
                    ->withFilter(new TaskProjectsFilter(array_keys($projects)))
                    ->getQuery()
                );
            $out = $paginator->getCollection();
        }
        return $out;
    }

    /**
     * Get an array with array of the getTimesByProjectId() method
     * for each project, which is active.
     *
     * @param integer $user
     * @return array
     */
    public function getTimesForAllActiveProjects()
    {
        $times = [];
        $projects = $this->projectUserRoleModel->getActiveProjectsByUser($this->userSession->getId());
        foreach ($projects as $projectId => $projectName) {
            $times[$projectId] = [
                'name' => $projectName,
                'times' => $this->getTimesByProjectId($projectId)
            ];
        }

        return $times;
    }

    /**
     * Get a times array for the tooltip on the tasks
     * detail page.
     *
     * @param  integer $task_id
     * @return array
     */
    public function getTimesForTooltipTaskTimes($task_id)
    {
        $subtasks = $this->getSubtasksByTaskId($task_id);

        // maybe the task only has own times, which means that there
        // cannot be ignord ones etc. so only output All
        if (empty($subtasks)) {
            return $this->getTimesForTooltipTaskTimesFromItsTimes($task_id);

        // task has subtasks; use them then!
        } else {
            return $this->getTimesForTooltipTaskTimesFromItsSubtasks($task_id);
        }
    }

    /**
     * Get a times array for the tooltip on the tasks
     * detail page based on the times of the task.
     *
     * @param  integer $task_id
     * @return array
     */
    public function getTimesForTooltipTaskTimesFromItsTimes($task_id)
    {
        $task = $this->taskFinderModel->getById($task_id);

        $this->getOvertimeForTask($task, false);

        return [
            'All' => $task,
        ];
    }

    /**
     * Get estimated times from the tasks subtasks instead
     * of its own times. This is needed, if the task has
     * different times than its subtasks.
     *
     * @param  array &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getEstimatedFromSubtasks(&$task, $use_ignore = 1)
    {
        $subtasks = $this->getSubtasksByTaskId($task['id']);
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
     * Get spent times from the tasks subtasks instead
     * of its own times. This is needed, if the task has
     * different times than its subtasks.
     *
     * @param  array &$task
     * @param  integer   $use_ignore
     *         1: get times and skip ignored subtasks
     *         2: get only ignored subtask times
     *         3: get ignored AND non-ignored subtasks times
     * @return float
     */
    public function getSpentFromSubtasks(&$task, $use_ignore = 1)
    {
        $subtasks = $this->getSubtasksByTaskId($task['id']);
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
     * Get a times array for the tooltip on the tasks
     * detail page based on the subtasks of the task.
     * Prepare three kinds of subtasks to be able to
     * correctly split the subtasks into:
     * 1. All times (ignored + not-ignored): key 'All'
     * 2. Only ignored times: key 'Ignored'
     * 3. Only not-ignored time: key 'Without ignored'
     *
     * @param  integer $task_id
     * @return array
     */
    public function getTimesForTooltipTaskTimesFromItsSubtasks($task_id)
    {
        $task_raw = $this->taskFinderModel->getById($task_id);

        $task_all = $this->calculateEstimatedSpentOvertimeForTask($task_raw, 3);
        $task_ignored = $this->calculateEstimatedSpentOvertimeForTask($task_raw, 2);
        $task_without_ignored = $this->calculateEstimatedSpentOvertimeForTask($task_raw, 1);

        return [
            'All' => $task_all,
            'Ignored' => $task_ignored,
            'Without ignored' => $task_without_ignored
        ];
    }

    /**
     * Calculate estimated, spent and overtime for the given
     * task array, depending on its subtasks. Also consider
     * the subtask title ignoring or not.
     *
     * Return a new task from the given one and do not modify
     * the given task in the argument.
     *
     * @param  array  $task
     * @param  integer $use_ignore
     * @return array
     */
    public function calculateEstimatedSpentOvertimeForTask($task, $use_ignore = 1)
    {
        $task_tmp = $task;
        $this->getEstimatedFromSubtasks($task_tmp, $use_ignore);
        $this->getSpentFromSubtasks($task_tmp, $use_ignore);
        $this->getOvertimeForTask($task_tmp, $use_ignore);
        return $task_tmp;
    }

    /**
     * Basically some kind of wrapper function for getting
     * the array with all the tasks for the project in the
     * given date range.
     *
     * @param  integer $projectId
     * @param  integer $start
     * @param  integer $end
     * @return array
     */
    protected function getAllTasksByProjectIdInDateRange($projectId, $start, $end)
    {
        $project = $this->projectModel->getById($projectId);

        $query = $this->taskFinderModel->getExtendedQuery()
            ->eq(TaskModel::TABLE.'.project_id', $projectId)
            ->gte(TaskModel::TABLE.'.date_modification', $start)
            ->lte(TaskModel::TABLE.'.date_modification', $end);

        $builder = $this->taskLexer;
        $builder->withQuery($query);
        return $builder->build('')->toArray();
    }

    /**
     * Generate a simple array, which will commonly
     * be used in my plugin to access times of the
     * task. E.g. it will generate a blank array
     * with the common time-keys like:
     * - time_estimated
     * - time_spent
     * - time_remaining
     * - time_overtime
     *
     * @return array
     */
    public function generateTaskTimesTemplate()
    {
        return [
            'time_estimated' => 0.0,
            'time_spent' => 0.0,
            'time_remaining' => 0.0,
            'time_overtime' => 0.0
        ];
    }

    /**
     * Add the times on the keys time_estimated,
     * time_spent, time_remaining and time_overtime
     * from one given task to the same keys of
     * the other given task.
     *
     * @param array &$modify_task
     * @param array $add_task
     * @return array
     */
    public function addTimesFromOneTaskToAnother(&$modify_task, $add_task)
    {
        $modify_task['time_estimated'] += $this->getEstimatedTimeForTask($add_task);
        $modify_task['time_spent'] += $this->getSpentTimeForTask($add_task);
        $modify_task['time_remaining'] += $this->getRemainingTimeForTask($add_task);
        $modify_task['time_overtime'] += $this->getOvertimeForTask($add_task);
        return $modify_task;
    }

    /**
     * Iter through the given tasks and try to access their
     * values under the keys time_estimated, time_spent,
     * time_remaining and time_overtime to calculate
     * a total out of them and return it as an array.
     *
     * Array will contain all tasks, consider ignored
     * subtasks and also non-ignored subtasks.
     *
     * Output will be:
     * [
     *     'All' => 0.0,
     *     'Ignored' => 0.0,
     *     'Without ignored' => 0.0
     * ]
     *
     * @param  array $tasks
     * @return array
     */
    public function generateTimesArrayFromTasksForWorkedTimesTooltip($tasks)
    {
        $tasks_all = $this->generateTaskTimesTemplate();
        $tasks_ignored = $this->generateTaskTimesTemplate();
        $tasks_without_ignored = $this->generateTaskTimesTemplate();

        foreach ($tasks as $task) {
            $task_all_tmp = $task;
            $task_ignored_tmp = $task;
            $task_without_ignored_tmp = $task;
            $this->addTimesFromOneTaskToAnother(
                $tasks_all, $this->calculateEstimatedSpentOvertimeForTask($task_all_tmp, 3)
            );
            $this->addTimesFromOneTaskToAnother(
                $tasks_ignored, $this->calculateEstimatedSpentOvertimeForTask($task_ignored_tmp, 2)
            );
            $this->addTimesFromOneTaskToAnother(
                $tasks_without_ignored, $this->calculateEstimatedSpentOvertimeForTask($task_without_ignored_tmp, 1)
            );
        }

        return [
            'All' => $tasks_all,
            'Ignored' => $tasks_ignored,
            'Without ignored' => $tasks_without_ignored
        ];
    }

    /**
     * Get the start and the end of a month
     * relative to the actual month and return
     * it as an array with [0] being the start
     * and [1] being the end. The format is
     * a simple unix timestamp then.
     *
     * @param  integer $relative
     * @return array
     */
    public function getMonthStartAndEnd($relative = 0)
    {
        // start
        $actualDate = strtotime('now');
        $firstDayOfMonth = strtotime(date('Y-m-01', $actualDate));
        $firstDayOfRelativeMonth = strtotime((string) $relative . ' month', $firstDayOfMonth);

        // end
        $firstDayOfMonthAfter = strtotime('+1 month', $firstDayOfRelativeMonth);
        $lastDayOfRelativeMonth = strtotime('-1 day', $firstDayOfMonthAfter);
        $lastDayOfRelativeMonth = strtotime('+23 hours +59 minutes +59 seconds', $lastDayOfRelativeMonth);

        return [$firstDayOfRelativeMonth, $lastDayOfRelativeMonth];
    }

    /**
     * Get times for actual or relative to actual month
     * for the given project and return it as an array.
     *
     * @param  integer $project_id
     * @param  integer $relative
     * @return array
     */
    public function getMonthTimes($project_id, $relative = 0)
    {
        // date boundaries
        $month = $this->getMonthStartAndEnd($relative);
        $start = $month[0];
        $end = $month[1];

        $all_tasks_raw = $this->getAllTasksByProjectIdInDateRange($project_id, $start, $end);

        return $this->generateTimesArrayFromTasksForWorkedTimesTooltip($all_tasks_raw);
    }

    /**
     * Get the start and the end of a weel
     * relative to the actual week and return
     * it as an array with [0] being the start
     * and [1] being the end. The format is
     * a simple unix timestamp then.
     *
     * @param  integer $relative
     * @return array
     */
    public function getWeekStartAndEnd($relative = 0)
    {
        // start
        $actualDate = strtotime('now');
        $firstDayOfWeek = strtotime('last monday', $actualDate);
        $firstDayOfRelativeWeek = strtotime((string) $relative . ' week', $firstDayOfWeek);

        // end
        $lastDayOfRelativeWeek = strtotime('next sunday', $firstDayOfRelativeWeek) + 86399; // 86399 seconds for 23:59:59

        return [$firstDayOfRelativeWeek, $lastDayOfRelativeWeek];
    }

    /**
     * Get times for actual or relative to actual month
     * for the given project and return it as an array.
     *
     * @param  integer $project_id
     * @param  integer $relative
     * @return array
     */
    public function getWeekTimes($project_id, $relative = 0)
    {
        // date boundaries
        $week = $this->getWeekStartAndEnd($relative);
        $start = $week[0];
        $end = $week[1];

        $all_tasks_raw = $this->getAllTasksByProjectIdInDateRange($project_id, $start, $end);

        return $this->generateTimesArrayFromTasksForWorkedTimesTooltip($all_tasks_raw);
    }

    /**
     * Get the start and the end of a day
     * relative to the actual day and return
     * it as an array with [0] being the start
     * and [1] being the end. The format is
     * a simple unix timestamp then.
     *
     * @param  integer $relative
     * @return array
     */
    public function getDayStartAndEnd($relative = 0)
    {
        $actualDate = strtotime('now');

        // start
        $startOfActualDay = strtotime(date('Y-m-d 00:00:00', $actualDate));
        $startOfActualDayRelative = strtotime((string) $relative . ' day', $startOfActualDay);

        // end
        $endOfActualDay = strtotime(date('Y-m-d 23:59:59', $actualDate));
        $endOfActualDayRelative = strtotime((string) $relative . ' day', $endOfActualDay);

        return [$startOfActualDayRelative, $endOfActualDayRelative];
    }

    /**
     * Get times for actual or relative to actual day
     * for the given project and return it as an array.
     *
     * @param  integer $project_id
     * @param  integer $relative
     * @return array
     */
    public function getDayTimes($project_id, $relative = 0)
    {
        // date boundaries
        $day = $this->getDayStartAndEnd($relative);
        $start = $day[0];
        $end = $day[1];

        $all_tasks_raw = $this->getAllTasksByProjectIdInDateRange($project_id, $start, $end);

        return $this->generateTimesArrayFromTasksForWorkedTimesTooltip($all_tasks_raw);
    }

    /**
     * Check the given array, if it contains any times
     * and returns a boolean accordingly.
     *
     * @param  array   $timesArray
     * @return boolean
     */
    public function hasTimes($timesArray = [])
    {
        if ($timesArray['estimated'] != 0.0 || $timesArray['spent'] != 0.0 || $timesArray['remaining'] != 0.0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Calculate the amount of blocks for the given time and respecting the
     * set config for "hoursview_block_hours".
     *
     * @param  float  $time The time to be used for the simple calculation.
     * @return integer
     */
    public function calcBlocksFromTime($time = 0.0)
    {
        $block_hours = (int) $this->configModel->get('hoursview_block_hours', 0);
        if ($block_hours == 0) {
            return 0;
        }
        return (int) ceil($time / $block_hours);
    }

    /**
     * A helper function to help sort the times array, when accessing the
     * times array in tooltip_dashboard_times.
     *
     * The function sorts the given level by the given key, which lays
     * deep nested in the array, actually, and returns a new sorted array.
     *
     * The method automatically uses the config to know how to sort.
     *
     * @param  array  $times
     * @param  string  $level
     * @return array
     */
    public function sortTimesArray($times, $level = 'level_1')
    {
        $tooltip_sorting = $this->configModel->get('hoursview_tooltip_sorting', 'id');
        if ($tooltip_sorting == 'id') {
            // by default the returned $times array should already
            // be in the sorting of 'id', which is the key of the
            // array.
            return $times;
        }

        // otherwise do the sorting thing now

        // this part interpretes the tooltip_sorting config
        if ($tooltip_sorting == 'remaining_hours_asc') {
            $key = 'remaining';
            $asc = true;
        } elseif ($tooltip_sorting == 'remaining_hours_desc') {
            $key = 'remaining';
            $asc = false;
        } else {
            $key = 'all';
            $asc = true;
        }

        // this one sorts with a custom function
        uasort($times, function ($a, $b) use ($level, $key, $asc) {
            if ($asc == true) {
                return $a['times'][$level]['_total'][$key] <=> $b['times'][$level]['_total'][$key];
            } else {
                return $b['times'][$level]['_total'][$key] <=> $a['times'][$level]['_total'][$key];
            }
        });

        return $times;
    }
}
