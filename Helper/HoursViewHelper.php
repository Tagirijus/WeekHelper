<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Pimple\Container;
use Kanboard\Core\Base;
use Kanboard\Model\TaskModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Core\Paginator;
use Kanboard\Filter\TaskProjectsFilter;
use Kanboard\Plugin\WeekHelper\Model\ProjectInfoParser;
use Kanboard\Plugin\WeekHelper\Model\TasksTimesPreparer;
use Kanboard\Plugin\WeekHelper\Model\TimesCalculator;


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
     * Extend the task array with its project info, which got parsed
     * through its description text.
     *
     * @param  array &$task
     */
    protected function extendTaskWithProjectInfo(&$task)
    {
        // if I need other (native Kanboard) project values to sort on,
        // I should add them here. Otherwise with just the key "info"
        // there are the ones added / parsed by my plugin.
        $task = array_merge(
            $task,
            $this->getProject($task['project_id'])['info']
        );
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
        foreach ($projects as &$project) {
            $project_id = $project['id'];
            $info = ProjectInfoParser::getProjectInfoByProject($project);
            $project['info'] = $info;
            $this->projects[$project_id] = $project;
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
            if (!array_key_exists($subtask['task_id'], $this->subtasks)) {
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

        // simply fetch all open tasks from all open projects
        // todo: maybe at some point this should be split into
        //       "all" and "all by user id". but at the moment
        //       I am the only person using my Kanboard, thus
        //       there is no need for me to separate between
        //       "open tasks" and "open tasks by user".
        } else {
            $query = $this->taskFinderModel->getExtendedQuery();
            $query->eq(ProjectModel::TABLE.'.is_active', 1);
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

        // now extend all the projects info to the tasks so that
        // later the TaskTimesPreparer can pass the tasks to the
        // SortingLogic with the needed keys for sorting.
        foreach ($this->tasks as &$task) {
            $this->extendTaskWithProjectInfo($task);
        }
        unset($task);

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
            'progress_home_project_level' => array_map(
                'trim',
                explode(',', $this->configModel->get('hoursview_progress_home_project_level', 'all'))
            ),
            'project_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id'),
            'sorting_logic' => $this->configModel->get('weekhelper_sorting_logic', ''),
            'timetagger_url' =>  $this->configModel->get('timetagger_url', ''),
            'timetagger_authtoken' =>  $this->configModel->get('timetagger_authtoken', ''),
            'timetagger_cookies' =>  $this->configModel->get('timetagger_cookies', ''),
            'timetagger_overwrites_levels' => $this->configModel->get('timetagger_overwrites_levels', ''),
            'timetagger_start_fetch' => $this->configModel->get('timetagger_start_fetch', ''),
        ];
        $this->task_times_preparer = new TasksTimesPreparer($config_task_times_preparer);
        $this->task_times_preparer->initTasksAndTimes(
            $this->getTasks(),
            $this->getSubtasks()
        );
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
        } elseif (
            $percent >= 100
            && TimesCalculator::isDone($task)
        ) {
            return 'progress-color-100';
        } elseif (
            $percent >= 100
            && !TimesCalculator::isDone($task)
        ) {
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
}
