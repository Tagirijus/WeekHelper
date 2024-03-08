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
            $all[$col_name]['estimated'] += $task['time_estimated'];
            $all[$col_name]['spent'] += $task['time_spent'];
            $all[$col_name]['remaining'] += $this->getRemainingTimeForTask($task);
            $all[$col_name]['overtime'] += $this->getOvertimeForTask($task);

            // all: total times
            $all['_total']['estimated'] += $task['time_estimated'];
            $all['_total']['spent'] += $task['time_spent'];
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
            $level[$col_name]['estimated'] += $task['time_estimated'];
            $level[$col_name]['spent'] += $task['time_spent'];
            $level[$col_name]['remaining'] += $this->getRemainingTimeForTask($task);
            $level[$col_name]['overtime'] += $this->getOvertimeForTask($task);

            // level: total times
            $level['_total']['estimated'] += $task['time_estimated'];
            $level['_total']['spent'] += $task['time_spent'];
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
        $tasks = $this->getTasksByProjectId($projectId);

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
                $out['estimated'] += $task['time_estimated'];
                $out['spent'] += $task['time_spent'];
                $out['remaining'] += $this->getRemainingTimeForTask($task);
                $out['overtime'] += $this->getOvertimeForTask($task);
            }
        }
        return $out;
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
    protected function getTasksByProjectId($projectId)
    {
        $project = $this->projectModel->getById($projectId);
        $search = $this->helper->projectHeader->getSearchQuery($project);

        $query = $this->taskFinderModel->getExtendedQuery()
            ->eq(TaskModel::TABLE.'.project_id', $projectId);

        $builder = $this->taskLexer;
        $builder->withQuery($query);
        return $builder->build($search)->toArray();
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
        ];
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
        if (!array_key_exists('time_remaining', $task)) {
            $this->initRemainingTimeForTask($task, $use_ignore);
        }
        return $task['time_remaining'];
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

            // calculate remaining or overtime based on subtasks
            if (!empty($subtasks)) {
                $tmp = $this->getRemainingFromSubtasks($subtasks, $use_ignore);

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
     *     if they exist in the config
     * use_ignore == 2
     *     This means that subtasktitles should be skipped,
     *     if they do not exist in the config
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
            ($use_ignore == 1 && in_array($subtask['title'], $this->getIgnoredSubtaskTitles()))
            ||
            ($use_ignore == 2 && !in_array($subtask['title'], $this->getIgnoredSubtaskTitles()))
        ) {
            return true;
        } else {
            return false;
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
     * @return float
     */
    protected function getRemainingFromSubtasks($subtasks = [], $use_ignore = 1)
    {
        $out = 0.0;
        foreach ($subtasks as $subtask) {
            if ($this->ignoreLogic($subtask, $use_ignore)) {
                continue;
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
        if (!array_key_exists('time_overtime', $task)) {
            $this->initOvertimeTimeForTask($task, $use_ignore);
        }
        return $task['time_overtime'];
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
        $estimated = $task['time_estimated'];
        $spent = $task['time_spent'];
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
        if (isset($task['time_estimated']) && isset($task['time_spent'])) {
            $estimated = $task['time_estimated'];

            // the given task must be a normal task-array
            if (array_key_exists('id', $task)) {
                $remaining = $this->getRemainingTimeForTask($task);
                $spent = $estimated - $remaining;

            // the given task might be a pseudo_task from
            // the project_times_summary_single.php; so no
            // real task-array with an id is given, but
            // probably a pre-calculated remaining instead
            } elseif (array_key_exists('time_remaining', $task)) {
                $remaining = $task['time_remaining'];
                $spent = $estimated - $remaining;

            // fallback: just use the normal time_spent
            } else {
                $spent = $task['time_spent'];
            }

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
        $modify_task['time_estimated'] += $add_task['time_estimated'];
        $modify_task['time_spent'] += $add_task['time_spent'];
        $modify_task['time_remaining'] += $add_task['time_remaining'];
        $modify_task['time_overtime'] += $add_task['time_overtime'];
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
        $lasDayOfRelativeMonth = strtotime('-1 day', $firstDayOfMonthAfter);
        $lasDayOfRelativeMonth = strtotime('+23 hours +59 minutes +59 seconds', $lasDayOfRelativeMonth);

        return [$firstDayOfRelativeMonth, $lasDayOfRelativeMonth];
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
}
