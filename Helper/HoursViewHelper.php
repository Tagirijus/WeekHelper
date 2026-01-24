<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Pimple\Container;
use Kanboard\Core\Base;
use Kanboard\Model\TaskModel;
// use Kanboard\Model\ProjectModel;
// use Kanboard\Model\SubtaskModel;
use Kanboard\Core\Paginator;
use Kanboard\Filter\TaskProjectsFilter;
use Kanboard\Plugin\WeekHelper\Model\TasksTimesPreparer;


class HoursViewHelper extends Base
{
    /**
     * Defines the used init method. There is some
     * priority logic. E.g. 'search' can be higher
     * than 'open', which can be higher than
     * 'project'. Higher means: it can re-initialize
     * internally. Otherwise no new initialization will
     * be made.
     *
     * @var string
     **/
    var $init_method = '';

    /**
     * Projects cache-variable:
     * [project_id => project_array]
     *
     * @var array
     **/
    var $projects = [];

    /**
     * Subtasks cache-variable:
     * [task_id => subtask_array]
     *
     * @var array
     **/
    var $subtasks = [];

    /**
     * Tasks cache-variable:
     * [task_id => task_array]
     *
     * @var array
     **/
    var $tasks = [];

    /**
     * The internal master class: the task preparer!
     *
     * @var TasksTimesPreparer
     **/
    var $task_times_preparer = null;

    /**
     * Defines which tasks will be used for internal
     * initialization and thus also calculation.
     *
     * Options are (as a string):
     * - open: all open tasks will be fetcht.
     * - project: all open tasks from a project will
     *   be fetcht. $this->task_init_project_id has
     *   to be set then as well!
     * - search: the search query will be used, as if
     *   the user is searching for tasks.
     *
     * @var string
     **/
    var $task_init_method = 'open';

    /**
     * The project id for the tasks initialization,
     * of $this->task_init_method is set to
     * 'project'.
     *
     * @var integer
     **/
    var $task_init_project_id = -1;

    /**
     * Constructor for HoursViewHelper
     *
     * @access public
     * @param  Container  $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Will check the given init method with the
     * internally already set one and return whether
     * the init process should be done again or not.
     *
     * E.g.: maybe I initialized the tasks with 'project'.
     * Now at another point I want to initialize with
     * 'open'. This should be able to re-initialize
     * the whole tasks, since there I need "more".
     * 'search' is even higher.
     *
     * @param  string $method
     * @return boolean
     */
    protected function initLogic($method)
    {
        $priorities = [
            '' => -1,
            'project' => 0,
            'open' => 1,
            'search' => 2,
        ];
        if (array_key_exists($method, $priorities)) {
            return $priorities[$method] > $priorities[$this->init_method];
        } else {
            return false;
        }
    }

    /**
     * Initialize the internal projects array with the
     * given project ids array.
     *
     * @param  array  $project_ids
     */
    protected function initProjects($project_ids = [])
    {
        $projects = $this->projectModel->getAllByIds($project_ids);
        $this->projects = [];
        foreach ($projects as $project) {
            $this->projects[$project['id']] = $project;
        }
    }

    /**
     * Initialize the internal subtasks cache variable.
     */
    protected function initSubtasks()
    {
        $subtasks = $this->subtaskModel->getAllByTaskIds(
            array_keys($this->getTasks())
        );
        $this->subtasks = [];
        foreach ($subtasks as $subtask) {
            if (array_key_exists($subtask['task_id'], $this->subtasks)) {
                $this->subtasks[$subtask['task_id']] = [];
            }
            $this->subtasks[$subtask['task_id']][] = $subtask;
        }
    }

    /**
     * Initialize the internal tasks cache variable.
     *
     * Options for $method are (as a string):
     * - open: all open tasks will be fetcht.
     * - project: all open tasks from a project will
     *   be fetcht. $project_id has to be set then as well!
     * - search: the search query will be used, as if
     *   the user is searching for tasks.
     *
     * @param string  $method
     * @param integer $project_id
     */
    public function initTasks($method = 'open', $project_id = -1)
    {
        if (!$this->initLogic($method)) {
            return;
        }

        // query tasks from the search params
        if ($method == 'search') {
            $tasks = [];
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
                $tasks = $paginator->getCollection();
            }

        // use project id to fetch tasks
        } elseif ($method == 'project') {
            $query = $this->taskFinderModel->getExtendedQuery()
                ->eq(TaskModel::TABLE.'.project_id', $project_id);

            $builder = $this->taskLexer;
            $builder->withQuery($query);
            $tasks = $builder->build('status:open')->toArray();

        // simply fetch all open tasks
        // todo: maybe at some point this should be split into
        //       "all" and "all by user id". but at the moment
        //       I am the only person using my Kanboard, thus
        //       there is no need for me to separate between
        //       "open tasks" and "open tasks by user".
        } else {
            $query = $this->taskFinderModel->getExtendedQuery();
            $builder = $this->taskLexer;
            $builder->withQuery($query);
            $tasks = $builder->build('status:open')->toArray();
        }

        // rearrange the array to have the tasks id as key
        // and also fill the temp array, which will store
        // all needed project ids, for the later project
        // initialization
        $this->tasks = [];
        $project_ids = [];
        foreach ($tasks as $task) {
            $this->tasks[$task['id']] = $task;
            if (!in_array($task['project_id'], $project_ids)) {
                $project_ids[] = $task['project_id'];
            }
        }
        unset($task);

        // also call initSubtasks() automatically now
        $this->initSubtasks();

        // and initialize the projects as well
        $this->initProjects($project_ids);

        // finally tell the instance, that it was initialized already,
        // basically
        $this->init_method = $method;
    }

    /**
     * Initialize the TasksTimesPreparer.
     */
    public function initTasksTimesPreparer()
    {
        $config_task_times_preparer = [
            'levels_config' => [
                'level_1' => $this->configModel->get('hoursview_level_1_columns', ''),
                'level_2' => $this->configModel->get('hoursview_level_2_columns', ''),
                'level_3' => $this->configModel->get('hoursview_level_3_columns', ''),
                'level_4' => $this->configModel->get('hoursview_level_4_columns', '')
            ],
            'non_time_mode_minutes' => $this->configModel->get('hoursview_non_time_mode_minutes', 0),
            'project_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id'),
            'sorting_logic' => $this->configModel->get('weekhelper_sorting_logic', ''),
            'timetagger_url' =>  $this->configModel->get('timetagger_url', ''),
            'timetagger_authtoken' =>  $this->configModel->get('timetagger_authtoken', ''),
            'timetagger_cookies' =>  $this->configModel->get('timetagger_cookies', ''),
            'timetagger_overwrites_levels_spent' => $this->configModel->get('timetagger_overwrites_levels_spent', ''),
            'timetagger_start_fetch' => $this->configModel->get('timetagger_start_fetch', ''),
        ];
        $this->task_times_preparer = new TasksTimesPreparer($config_task_times_preparer);
        $this->task_times_preparer->initTasksAndTimes(
            $this->getTasks(),
            $this->getSubtasks()
        );
    }

    // /**
    //  * Get all tasks from the the search URI,
    //  * ignoring the pagination.
    //  *
    //  * ATTENTION:
    //  *     This method might be overcomplicated, since
    //  *     it uses the Paginator() class to get the
    //  *     tasks. There might be a smarter way to
    //  *     query the tasks with the given search string,
    //  *     but I had no time to dive deeper into the
    //  *     Kanboard framework to find out how. Yet
    //  *     the PicoDB thing seems quite nice, though.
    //  *     So maybe some day I might improve this
    //  *     method or make it more logical, since it
    //  *     is not that clever (to me) to use the
    //  *     paginator for such a query. I do not
    //  *     need "other pages" after all.
    //  *
    //  *     This method is only for getting ALL tasks
    //  *     with the given search string.
    //  *
    //  * @return array
    //  */
    // public function getAllTasksFromSearch()
    // {
    //     $out = [];
    //     $projects = $this->projectUserRoleModel->getActiveProjectsByUser($this->userSession->getId());
    //     $search = urldecode($this->request->getStringParam('search'));
    //     if ($search !== '' && ! empty($projects)) {
    //         $paginator = new Paginator($this->container);
    //         $paginator
    //             ->setMax(999999)
    //             ->setFormatter($this->taskListFormatter)
    //             ->setQuery($this->taskLexer
    //                 ->build($search)
    //                 ->withFilter(new TaskProjectsFilter(array_keys($projects)))
    //                 ->getQuery()
    //             );
    //         $out = $paginator->getCollection();
    //     }
    //     return $out;
    // }

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
            'tooltip_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id'),
            'dashboard_link_level_1' => $this->configModel->get('hoursview_dashboard_link_level_1', 0),
            'dashboard_link_level_2' => $this->configModel->get('hoursview_dashboard_link_level_2', 0),
            'dashboard_link_level_3' => $this->configModel->get('hoursview_dashboard_link_level_3', 0),
            'dashboard_link_level_4' => $this->configModel->get('hoursview_dashboard_link_level_4', 0),
            'dashboard_link_level_all' => $this->configModel->get('hoursview_dashboard_link_level_all', 0),
        ];
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

    // /**
    //  * Simply get all open tasks.
    //  *
    //  * @return array
    //  */
    // protected function getOpenTasks()
    // {
    //     $query = $this->taskFinderModel->getExtendedQuery();
    //     $builder = $this->taskLexer;
    //     $builder->withQuery($query);
    //     return $builder->build('status:open')->toArray();
    // }

    /**
     * This method will create a string based on the logic
     * with spent and overtime. It basically will "split"
     * the value apart and output some kind of calculation
     * to better visualize, whether I was quicker or
     * slower.
     *
     * This is used in the tooltip_dashboard_times template.
     *
     * @param  float $spent
     * @param  float $overtime
     * @return string
     */
    public function getOvertimeInfo($spent, $overtime)
    {
        $out = $this->getTimes()->floatToHHMM($spent - $overtime) . 'h ';

        if ($overtime > 0) {
            $prefix = '+ ';
        } else {
            $prefix = '- ';
        }

        return $out . $prefix . $this->getTimes()->floatToHHMM(abs($overtime)) . 'h';
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
     * Get a project array with the given id, if it
     * exists in the internal cache variable.
     *
     * @param  integer $project_id
     * @return array
     */
    public function getProject($project_id)
    {
        return $this->projects[$project_id] ?? [];
    }

    /**
     * Get all subtasks of open tasks, or all subtasks
     * defined through the search query of tasks.
     *
     * initTasks() with the needed options should
     * be called first. Otherwise no tasks will be
     * fetcht.
     *
     * @return array
     */
    public function getSubtasks()
    {
        return $this->subtasks;
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
     * Get all open task, or all tasks defined
     * by the search query.
     *
     * initTasks() with the needed options should
     * be called first. Otherwise no tasks will be
     * fetcht.
     *
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    // /**
    //  * Get the TasksTimesPreparer tasks_per_level array.
    //  *
    //  * @return array
    //  */
    // public function getTasksPerLevel()
    // {
    //     return $this->getTimes()->getTasksPerLevel();
    // }

    /**
     * Get the TasksTimesPreparer and, if needed, initialize it first.
     *
     * @return TasksTimesPreparer
     */
    public function getTimes()
    {
        if (is_null($this->task_times_preparer)) {
            $this->initTasksTimesPreparer();
        }
        return $this->task_times_preparer;
    }

    // /**
    //  * Get the estimated and spent times in the columns for
    //  * the total (all) and the levels (level_1, level_2, ...).
    //  *
    //  * @param  array &$tasks
    //  * @return array
    //  */
    // public function getTimesFromTasks(&$tasks)
    // {
    //     $subtasks_by_task_id = [];
    //     foreach ($tasks as $task) {
    //         if (!isset($subtasks_by_task_id[$task['id']])) {
    //             $subtasks_by_task_id[$task['id']] = $this->getSubtasksByTaskId($task['id']);
    //         }
    //     }
    //     return $this->getTimes()->getTimesFromTasks($tasks, $subtasks_by_task_id);
    // }

    // /**
    //  * Get the estimated and spent times in the columns for
    //  * all tasks with a given project id.
    //  *
    //  * This method wraps basically the getTimesFromTasks()
    //  * method, but with a given project id to get the
    //  * linked tasks.
    //  *
    //  * @param  integer $projectId
    //  * @return array
    //  */
    // public function getTimesByProjectId($projectId)
    // {
    //     $tasks = $this->getOpenTasksByProjectId($projectId);

    //     return $this->getTimesFromTasks($tasks);
    // }

    // /**
    //  * Get an array with the calculated times for
    //  * the given column array.
    //  *
    //  * Array output:
    //  *
    //  * [
    //  *     'estimated' => 2,
    //  *     'spent' => 1,
    //  *     'remaining' => 1,
    //  *     'overtime' => 0
    //  * ]
    //  *
    //  * @param  array $column
    //  * @return array
    //  */
    // public function getTimesForColumn($column)
    // {
    //     $out = ['estimated' => 0, 'spent' => 0, 'remaining' => 0, 'overtime' => 0];
    //     if (isset($column['tasks'])) {
    //         foreach ($column['tasks'] as $task) {
    //             $out['estimated'] += $this->getEstimatedTimeForTask($task);
    //             $out['spent'] += $this->getSpentTimeForTask($task);
    //             $out['remaining'] += $this->getRemainingTimeForTask($task);
    //             $out['overtime'] += $this->getOvertimeForTask($task);
    //         }
    //     }
    //     return $out;
    // }

    // /**
    //  * Basically some kind of wrapper function for getting
    //  * the array with all the tasks for the project.
    //  *
    //  * @param  integer $projectId
    //  * @return array
    //  */
    // protected function getOpenTasksByProjectId($projectId)
    // {
    //     // this is not needed anymore, since I just want to get open
    //     // tasks anyway, which would get "status:open" here anyway.
    //     // $search = $this->helper->projectHeader->getSearchQuery($project);

    //     $query = $this->taskFinderModel->getExtendedQuery()
    //         ->eq(TaskModel::TABLE.'.project_id', $projectId);

    //     $builder = $this->taskLexer;
    //     $builder->withQuery($query);
    //     return $builder->build('status:open')->toArray();
    // }

    // /**
    //  * This one gets all tasks for the user and their
    //  * respecting times.
    //  *
    //  * Array output:
    //  *
    //  * [
    //  *     'estimated' => 2,
    //  *     'spent' => 1
    //  * ]
    //  *
    //  * @param  integer $userId
    //  * @return array
    //  */
    // public function getTimesByUserId($userId)
    // {
    //     $tasks = $this->taskFinderModel->getExtendedQuery()
    //         ->beginOr()
    //         ->eq(TaskModel::TABLE.'.owner_id', $userId)
    //         ->addCondition(TaskModel::TABLE.".id IN (SELECT task_id FROM ".SubtaskModel::TABLE." WHERE ".SubtaskModel::TABLE.".user_id='$userId')")
    //         ->closeOr()
    //         ->eq(TaskModel::TABLE.'.is_active', TaskModel::STATUS_OPEN)
    //         ->eq(ProjectModel::TABLE.'.is_active', ProjectModel::ACTIVE)
    //         ->findAll();

    //     return $this->getTimesFromTasks($tasks);
    // }

    // /**
    //  * Represent the given float as a proper time string.
    //  *
    //  * @param  float $time
    //  * @return string
    //  */
    // public function floatToHHMM($time)
    // {
    //     if ($time < 0) {
    //         $time = $time * -1;
    //         $negative = true;
    //     } else {
    //         $negative = false;
    //     }
    //     $hours = (int) $time;
    //     $minutes = fmod((float) $time, 1) * 60;
    //     if ($negative) {
    //         return sprintf('-%01d:%02d', $hours, $minutes);
    //     } else {
    //         return sprintf('%01d:%02d', $hours, $minutes);
    //     }
    // }

    // /**
    //  * Get the estimated time of a given task according to internal settings.
    //  *
    //  * @param  array  &$task
    //  * @return float
    //  */
    // public function getEstimatedTimeForTask(&$task)
    // {
    //     return $this->getTimes()->getEstimatedTimeForTask($task);
    // }

    // /**
    //  * Get the spent time of a given task according to internal settings.
    //  *
    //  * @param  array  &$task
    //  * @return float
    //  */
    // public function getSpentTimeForTask(&$task)
    // {
    //     return $this->getTimes()->getSpentTimeForTask(
    //         $task,
    //         $this->getSubtasksByTaskId($task['id'])
    //     );
    // }

    // /**
    //  * Init maybe and then return the remaining time
    //  * for the given task.
    //  *
    //  * @param  array  &$task
    //  * @return float
    //  */
    // public function getRemainingTimeForTask(&$task)
    // {
    //     return $this->getTimes()->getRemainingTimeForTask(
    //         $task,
    //         $this->getSubtasksByTaskId($task['id'])
    //     );
    // }

    // /**
    //  * Init maybe and then return the overtime time
    //  * for the given task.
    //  *
    //  * @param  array  &$task
    //  * @return float
    //  */
    // public function getOvertimeForTask(&$task)
    // {
    //     return $this->getTimes()->getOvertimeForTask(
    //         $task,
    //         $this->getSubtasksByTaskId($task['id'])
    //     );
    // }

    // /**
    //  * Get the overtime with the correct sign to
    //  * show in the header.
    //  *
    //  * E.g. either there was overtime; then it will
    //  * be shown as "time_estimated" + "overtime".
    //  *
    //  * If you worked faster it's "time_estimated" - "overtime".
    //  *
    //  * @param  float $overtime
    //  * @return string
    //  */
    // public function getOvertimeForTaskAsString($overtime)
    // {
    //     if ($overtime > 0) {
    //         $prefix = '+ ';
    //     } else {
    //         $prefix = '- ';
    //     }
    //     return $prefix . $this->floatToHHMM(abs($overtime)) . 'h';
    // }

    // /**
    //  * With the consideration of the subtask status, a subtask
    //  * might be done earlier than estimated. This way there might be
    //  * an available overhead-time. Or even vice versa and there
    //  * is less time left, since I mis-estimated the times.
    //  *
    //  * In either way this method is for calculating the difference.
    //  *
    //  * @param  array &$task
    //  * @return float
    //  */
    // public function getSlowerOrFasterThanEstimatedForTask(&$task)
    // {
    //     $remaining = $this->getRemainingTimeForTask($task);
    //     $estimated = $this->getEstimatedTimeForTask($task);
    //     $spent = $this->getSpentTimeForTask($task);
    //     return $estimated - $spent - $remaining;
    // }

    // /**
    //  * Wrapper for the getSlowerOrFasterThanEstimatedForTask()
    //  * method to render the ouput sign.
    //  *
    //  * @param  array &$task
    //  * @return string
    //  */
    // public function getSlowerOrFasterSign(&$task)
    // {
    //     $slowerOrFaster = $this->getSlowerOrFasterThanEstimatedForTask($task);
    //     if ($slowerOrFaster > 0) {
    //         $out = '>>';
    //     } else {
    //         $out = '<<';
    //     }
    //     // how it was before:
    //     // $out .= $this->floatToHHMM(abs($slowerOrFaster)) . ' h';
    //     return $out;
    // }

    // /**
    //  * Calculate the percent with the given task.
    //  * Use the times for this.
    //  *
    //  * Future idea:
    //  *    Maybe use the amount of subtasks, if no
    //  *    estimstd times exist at all.
    //  *
    //  * @param  array &$task
    //  * @param  bool $overtime
    //  * @return integer
    //  */
    // public function getPercentForTask(&$task, $overtime = false)
    // {
    //     $out = 0;

    //     // Calculate percentage from given times, while considering
    //     // the possible subtask times. These can vary, since
    //     // done subtasks will use the spent time as their
    //     // estimated time, if they are done already. This would
    //     // mean less (or sometimes more!) estimated overall time
    //     // after all. To do so I won't simply calculate
    //     // "spent / estimated" for the percentage, but rather:
    //     //      "(estimated - remaining) / estimated"
    //     //
    //     // Yet I can only do so, if the given $task is really a
    //     // task array with an 'id'; otherwise just do the normal
    //     // calculation instead ...
    //     if ($this->getEstimatedTimeForTask($task) != 0) {
    //         $estimated = $this->getEstimatedTimeForTask($task);
    //         $remaining = $this->getRemainingTimeForTask($task);
    //         $spent = $estimated - $remaining;

    //         if ($estimated != 0) {
    //             $out = round($spent / $estimated * 100, 0);
    //         } else {
    //             $out = 100;
    //         }
    //     }

    //     // consider overtime
    //     if ($overtime) {
    //         if ($out > 100) {
    //             $out = $out - 100;
    //         } else {
    //             $out = 0;
    //         }
    //     }

    //     // prevent negative percentages, which
    //     // might occur due to rounding issues,
    //     // I guess? - monkey patch!
    //     if ($out <= 0) {
    //         $out = 0;
    //     }

    //     return $out;
    // }

    // /**
    //  * Get percentage for a task according to its
    //  * spent time and estimated time (or in the future
    //  * maybe depending on the subtasks) and render
    //  * it as a string with percentage symbol.
    //  *
    //  * Also there is the option to add additional info like
    //  * the overtime.
    //  *
    //  * @param  array &$task
    //  * @param  string $symbol
    //  * @param  bool $overtime
    //  * @return string
    //  */
    // public function getPercentForTaskAsString(&$task, $symbol = '%', $overtime = false)
    // {
    //     $percent_over = $this->getPercentForTask($task, true);

    //     if ($overtime && $percent_over > 0) {
    //         $out = '100' . $symbol . ' (+' . $this->getPercentForTask($task, true) . $symbol . ')';
    //     } else {
    //         $out = $this->getPercentForTask($task, false) . $symbol;
    //     }

    //     return $out;
    // }

    // /**
    //  * According to the wanted levels from the config,
    //  * sum up all the respecting time values for e.g.
    //  * the "project_times_summary_single.php".
    //  *
    //  * @param  array $times
    //  * @return array
    //  */
    // public function prepareProjectTimesWithConfig($times)
    // {
    //     $out = [
    //         'estimated' => 0,
    //         'spent' => 0,
    //         'remaining' => 0,
    //         'overtime' => 0,
    //     ];

    //     // Get levels from config
    //     $levels = explode(',', $this->configModel->get('hoursview_progress_home_project_level', 'all'));

    //     // iter through levels, while checking if they exist in the $times as key
    //     foreach ($levels as $level) {
    //         if (array_key_exists($level, $times)) {
    //             $out['estimated'] += $times[$level]['_total']['estimated'];
    //             $out['spent'] += $times[$level]['_total']['spent'];
    //             $out['remaining'] += $times[$level]['_total']['remaining'];
    //             $out['overtime'] += $times[$level]['_total']['overtime'];
    //         }
    //     }

    //     return $out;
    // }

    // /**
    //  * Get an array with array of the getTimesByProjectId() method
    //  * for each project, which is active.
    //  *
    //  * @param integer $user
    //  * @return array
    //  */
    // public function getTimesForAllActiveProjects()
    // {
    //     $times = [];
    //     $projects = $this->projectUserRoleModel->getActiveProjectsByUser($this->userSession->getId());
    //     foreach ($projects as $projectId => $projectName) {
    //         $times[$projectId] = [
    //             'name' => $projectName,
    //             'times' => $this->getTimesByProjectId($projectId)
    //         ];
    //     }

    //     return $times;
    // }

    // /**
    //  * Check the given array, if it contains any times
    //  * and returns a boolean accordingly.
    //  *
    //  * @param  array   $timesArray
    //  * @return boolean
    //  */
    // public function hasTimes($timesArray = [])
    // {
    //     if ($timesArray['estimated'] != 0.0 || $timesArray['spent'] != 0.0 || $timesArray['remaining'] != 0.0) {
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }

    // /**
    //  * A helper function to help sort the times array, when accessing the
    //  * times array in tooltip_dashboard_times.
    //  *
    //  * The function sorts the given level by the given key, which lays
    //  * deep nested in the array, actually, and returns a new sorted array.
    //  *
    //  * The method automatically uses the config to know how to sort.
    //  *
    //  * @param  array  $times
    //  * @param  string  $level
    //  * @return array
    //  */
    // public function sortTimesArray($times, $level = 'level_1')
    // {
    //     $tooltip_sorting = $this->configModel->get('hoursview_tooltip_sorting', 'id');
    //     if ($tooltip_sorting == 'id') {
    //         // by default the returned $times array should already
    //         // be in the sorting of 'id', which is the key of the
    //         // array.
    //         return $times;
    //     }

    //     // otherwise do the sorting thing now

    //     // this part interpretes the tooltip_sorting config
    //     if ($tooltip_sorting == 'remaining_hours_asc') {
    //         $key = 'remaining';
    //         $asc = true;
    //     } elseif ($tooltip_sorting == 'remaining_hours_desc') {
    //         $key = 'remaining';
    //         $asc = false;
    //     } else {
    //         $key = 'all';
    //         $asc = true;
    //     }

    //     // this one sorts with a custom function
    //     uasort($times, function ($a, $b) use ($level, $key, $asc) {
    //         if ($asc == true) {
    //             return $a['times'][$level]['_total'][$key] <=> $b['times'][$level]['_total'][$key];
    //         } else {
    //             return $b['times'][$level]['_total'][$key] <=> $a['times'][$level]['_total'][$key];
    //         }
    //     });

    //     return $times;
    // }
}
