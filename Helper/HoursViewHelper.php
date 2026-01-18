<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Pimple\Container;
use Kanboard\Core\Base;
use Kanboard\Model\TaskModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\SubtaskModel;
use Kanboard\Core\Paginator;
use Kanboard\Filter\TaskProjectsFilter;
use Kanboard\Plugin\WeekHelper\Model\TaskTimesPreparer;


class HoursViewHelper extends Base
{
    /**
     * Cache variable for the config so that
     * it does not have to be fetched multiple
     * times on every method call, which is
     * using this config.
     *
     * @var integer
     **/
    var $block_hours = -1;

    /**
     * Subtasks cache-variable:
     * [task_id => subtask_array]
     *
     * @var array
     **/
    var $subtasks = [];

    /**
     * The internal master class: the task preparer!
     *
     * @var TaskTimesPreparer
     **/
    var $task_times_preparer;

    /**
     * Constructor for HoursViewHelper
     *
     * @access public
     * @param  Container  $container
     */
    public function __construct($container)
    {
        $this->container = $container;
        $config_task_times_preparer = [
            'level_1_columns' => $this->configModel->get('hoursview_level_1_columns', ''),
            'level_2_columns' => $this->configModel->get('hoursview_level_2_columns', ''),
            'level_3_columns' => $this->configModel->get('hoursview_level_3_columns', ''),
            'level_4_columns' => $this->configModel->get('hoursview_level_4_columns', ''),
            'non_time_mode_minutes' => $this->configModel->get('hoursview_non_time_mode_minutes', 0),
            'sorting_logic' => $this->configModel->get('weekhelper_sorting_logic', ''),
            'timetagger_url' =>  $this->configModel->get('timetagger_url', ''),
            'timetagger_authtoken' =>  $this->configModel->get('timetagger_authtoken', ''),
            'timetagger_cookies' =>  $this->configModel->get('timetagger_cookies', ''),
            'timetagger_overwrites_levels_spent' => $this->configModel->get('timetagger_overwrites_levels_spent', ''),
            'timetagger_start_fetch' => $this->configModel->get('timetagger_start_fetch', ''),
        ];
        $this->task_times_preparer = new TaskTimesPreparer($config_task_times_preparer);
    }

    /**
     * Get config for block hours and maybe initialize it first.
     *
     * @return integer
     */
    public function getBlockHours()
    {
        if ($this->block_hours == -1) {
            $this->block_hours = (int) $this->configModel->get('hoursview_block_hours', 0);
        }
        return $this->block_hours;
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
        }
        return $this->subtasks[$taskId];
    }

    /**
     * Get the TaskTimesPreparer tasks_per_level array.
     *
     * @return array
     */
    public function getTasksPerLevel()
    {
        return $this->task_times_preparer->getTasksPerLevel();
    }

    /**
     * Get the estimated and spent times in the columns for
     * the total (all) and the levels (level_1, level_2, ...).
     *
     * @param  array &$tasks
     * @return array
     */
    public function getTimesFromTasks(&$tasks)
    {
        $subtasks_by_task_id = [];
        foreach ($tasks as $task) {
            if (!isset($subtasks_by_task_id[$task['id']])) {
                $subtasks_by_task_id[$task['id']] = $this->getSubtasksByTaskId($task['id']);
            }
        }
        return $this->task_times_preparer->getTimesFromTasks($tasks, $subtasks_by_task_id);
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
     * Get the bool if the non-time-mode is enabled or not.
     */
    public function getNonTimeModeEnabled()
    {
        return $this->task_times_preparer->getNonTimeModeEnabled();
    }

    /**
     * Simply get all open tasks.
     *
     * @return array
     */
    public function getOpenTasks()
    {
        $query = $this->taskFinderModel->getExtendedQuery();
        $builder = $this->taskLexer;
        $builder->withQuery($query);
        return $builder->build('status:open')->toArray();
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
     * Get the estimated time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @return float
     */
    public function getEstimatedTimeForTask(&$task)
    {
        return $this->task_times_preparer->getEstimatedTimeForTask($task);
    }

    /**
     * Get the spent time of a given task according to internal settings.
     *
     * @param  array  &$task
     * @return float
     */
    public function getSpentTimeForTask(&$task)
    {
        return $this->task_times_preparer->getSpentTimeForTask(
            $task,
            $this->getSubtasksByTaskId($task['id'])
        );
    }

    /**
     * Init maybe and then return the remaining time
     * for the given task.
     *
     * @param  array  &$task
     * @return float
     */
    public function getRemainingTimeForTask(&$task)
    {
        return $this->task_times_preparer->getRemainingTimeForTask(
            $task,
            $this->getSubtasksByTaskId($task['id'])
        );
    }

    /**
     * Init maybe and then return the overtime time
     * for the given task.
     *
     * @param  array  &$task
     * @return float
     */
    public function getOvertimeForTask(&$task)
    {
        return $this->task_times_preparer->getOvertimeForTask(
            $task,
            $this->getSubtasksByTaskId($task['id'])
        );
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
     * Get a times array for the tooltip on the tasks
     * detail page based on the subtasks of the task.
     *
     * @param  integer $task_id
     * @return array
     */
    public function getTimesForTooltipTaskTimesFromItsSubtasks($task_id)
    {
        $task_raw = $this->taskFinderModel->getById($task_id);

        $task_all = $this->calculateEstimatedSpentOvertimeForTask($task_raw);

        return [
            'All' => $task_all,
        ];
    }

    /**
     * Calculate estimated, spent and overtime for the given
     * task array, depending on its subtasks. Also consider
     * the subtask title ignoring or not.
     *
     * Return a new task from the given one and do not modify
     * the given task in the argument. TODO: maybe non-referencing it
     *                                       is enough already?
     *
     * @param  array  $task
     * @return array
     */
    public function calculateEstimatedSpentOvertimeForTask($task)
    {
        $task_tmp = $task;
        $this->task_times_preparer->getEstimatedFromSubtasks(
            $task_tmp,
            $this->getSubtasksByTaskId($task_tmp['id'])
        );
        $this->task_times_preparer->getSpentFromSubtasks(
            $task_tmp,
            $this->getSubtasksByTaskId($task_tmp['id'])
        );
        $this->task_times_preparer->getOvertimeForTask(
            $task_tmp,
            $this->getSubtasksByTaskId($task_tmp['id'])
        );
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
     * Output will be:
     * [
     *     'All' => 0.0,
     *     (in older versions there were also the keys "ingored"
     *     and "without_ignored" here)
     * ]
     *
     * @param  array $tasks
     * @return array
     */
    public function generateTimesArrayFromTasksForWorkedTimesTooltip($tasks)
    {
        $tasks_all = $this->generateTaskTimesTemplate();

        foreach ($tasks as $task) {
            $task_all_tmp = $task;
            $this->addTimesFromOneTaskToAnother(
                $tasks_all, $this->calculateEstimatedSpentOvertimeForTask($task_all_tmp)
            );
        }

        return [
            'All' => $tasks_all,
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
        if ($this->getBlockHours() == 0) {
            return 0;
        }
        return (int) ceil($time / $this->getBlockHours());
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
