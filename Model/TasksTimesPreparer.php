<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Model\SortingLogic;
use Kanboard\Plugin\WeekHelper\Model\TaskDataExtender;
use Kanboard\Plugin\WeekHelper\Model\TimesCalculator;
use Kanboard\Plugin\WeekHelper\Model\TimesData;
use Kanboard\Plugin\WeekHelper\Model\TimesDataByEntity;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerFetcher;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerTranscriber;


class TasksTimesPreparer
{
    /**
     * The config values, which are outside stored
     * in the Kanboard config database probably.
     *
     * @var array
     **/
    var $config = [
        'levels_config' => [],
        'non_time_mode_minutes' => 0,
        'progress_home_project_level' => ['all'],
        // project_sorting can be:
        //    'id', 'remaining_hours_asc', 'remaining_hours_desc'
        'project_sorting' => 'id',
        'sorting_logic' => '',
        'timetagger_url' => '',
        'timetagger_authtoken' => '',
        'timetagger_cookies' => '',
        'timetagger_overwrites_levels' => '',
        'timetagger_start_fetch' => '',
    ];

    /**
     * Just the project ids by level.
     *
     * @var array
     **/
    var $project_ids_by_level = [];

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
     * The class attribute, holding the tasks by level.
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
    var $tasks_by_level = [];

    /**
     * Simply all times as a TimesData instance.
     *
     * @var TimesData
     **/
    var $times;

    /**
     * Times for all columns individually.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_column;

    /**
     * Times for all levels individually.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_level;

    /**
     * Times by project.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_project;

    /**
     * Times by project+level.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_project_level;

    /**
     * Times by project on dashboard / home.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_project_home;

    /**
     * Times for all swimlanes individually.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_swimlane;

    /**
     * Times for all swimlanes+columns individually.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_swimlane_column;

    /**
     * Times by task.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_task;

    /**
     * Times by user.
     *
     * @var TimesDataByEntity
     **/
    var $times_by_user;

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
        $this->times_by_column = new TimesDataByEntity();
        $this->times_by_level = new TimesDataByEntity();
        $this->times_by_project = new TimesDataByEntity();
        $this->times_by_project_level = new TimesDataByEntity();
        $this->times_by_project_home = new TimesDataByEntity();
        $this->times_by_swimlane = new TimesDataByEntity();
        $this->times_by_swimlane_column = new TimesDataByEntity();
        $this->times_by_task = new TimesDataByEntity();
        $this->times_by_user = new TimesDataByEntity();
    }

    /**
     * Store project info into internal variables for easier
     * access later on.
     *
     * @param array $task
     */
    public function addProjectInfo($task)
    {
        foreach (($task['levels'] ?? []) as $level) {
            if (!array_key_exists($level, $this->project_ids_by_level)) {
                $this->project_ids_by_level[$level] = [];
            }
            if (!array_key_exists('all', $this->project_ids_by_level)) {
                $this->project_ids_by_level['all'] = [];
            }
            if (!in_array($task['project_id'], $this->project_ids_by_level[$level])) {
                $this->project_ids_by_level[$level][] = $task['project_id'];
            }
            if (!in_array($task['project_id'], $this->project_ids_by_level['all'])) {
                $this->project_ids_by_level['all'][] = $task['project_id'];
            }
        }
    }

    /**
     * Add the given task to the internal tasks_by_level array, depending
     * on it's level. This info at this point should already be parsed
     * be the TaskDataExtender, thus the key 'levels' should exist.
     *
     * @param array &$task
     */
    public function addTaskToLevel(&$task)
    {
        foreach (($task['levels'] ?? []) as $level) {
            if (!array_key_exists($level, $this->tasks_by_level)) {
                $this->tasks_by_level[$level] = [];
            }
            $this->tasks_by_level[$level][$task['id']] = &$task;
        }
    }

    /**
     * Add the given times to the internal values, which do
     * depend on the level.
     *
     * The level info at this point should already be parsed be
     * the TaskDataExtender, thus the key 'levels' should exist.
     *
     * @param float $estimated
     * @param float $spent
     * @param float $remaining
     * @param float $overtime
     * @param array $task
     */
    public function addTimesLevelDepending(
        $estimated,
        $spent,
        $remaining,
        $overtime,
        $task
    )
    {
        foreach (($task['levels'] ?? []) as $level) {
            $this->times_by_level->addTimes(
                $estimated, $spent, $remaining, $overtime, $level
            );
            $this->times_by_project_level->addTimes(
                $estimated, $spent, $remaining, $overtime, $task['project_id'] . $level
            );
            if (
                in_array($level, $this->getConfig('progress_home_project_level'))
                || in_array('all', $this->getConfig('progress_home_project_level'))
            ) {
                $this->times_by_project_home->addTimes(
                    $estimated, $spent, $remaining, $overtime, $task['project_id']
                );
            }
        }
        $this->times_by_level->addTimes(
            $estimated, $spent, $remaining, $overtime, 'all'
        );
        $this->times_by_project_level->addTimes(
            $estimated, $spent, $remaining, $overtime, $task['project_id'] . 'all'
        );
    }

    /**
     * Clear internal values and make them "empty".
     */
    protected function emptyInternalValues()
    {
        $this->tasks = [];
        $this->tasks_by_level = [];
        $this->times->resetTimes();
        $this->times_by_level->resetTimes();
        $this->times_by_project->resetTimes();
        $this->times_by_user->resetTimes();
    }

    /**
     * Extend the parsed info for the given tasks.
     * A task can have certain values given in the description text,
     * which can be parsed into task array keys. e.g. "project_type"
     * can be overwritten here, etc.
     *
     * @param  array &$tasks
     */
    protected function extendTasksData(&$tasks)
    {
        foreach ($tasks as &$task) {
            TaskDataExtender::extendTask(
                $task,
                $this->getConfig('levels_config'),
                $this->getConfig('timetagger_overwrites_levels')
            );
        }
        unset($task);
    }

    /**
     * Wrapper for TimesData::flotToHHMM, which can be used in templates
     * later then.
     *
     * @param  float $time
     * @return string
     */
    public function floatToHHMM($time)
    {
        return TimesData::floatToHHMM($time);
    }

    /**
     * Get has_times by project.
     *
     * @param  integer $project_id
     * @return boolean
     */
    public function hasTimesByProject($project_id = -1)
    {
        return $this->times_by_project->hasTimes($project_id);
    }

    /**
     * Get has_times by project on home.
     *
     * @param  integer $project_id
     * @return boolean
     */
    public function hasTimesByProjectHome($project_id = -1)
    {
        return $this->times_by_project_home->hasTimes($project_id);
    }

    /**
     * Get has_time by project + level.
     *
     * @param  integer $project_id
     * @param  string $level
     * @return boolean
     */
    public function hasTimesByProjectLevel($project_id = -1, $level = '')
    {
        return $this->times_by_project_level->hasTimes($project_id . $level);
    }

    /**
     * Init the config with the given array.
     *
     * @param  array  $config
     */
    protected function initConfig($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Initialize all the important internal cache variables
     * with the given tasks and subtasks.
     *
     * Technically this method iters through all given tasks,
     * calculates their times and adds their times (and the
     * tasks themself) into the internal variables, if some
     * logic applies and the tasks / times should be in that
     * variable.
     *
     * @param  array  $tasks
     * @param  array  $subtasks_by_task_id Key is task id and values are the subtasks
     */
    public function initTasksAndTimes($tasks = [], $subtasks_by_task_id = [])
    {
        $this->extendTasksData($tasks);
        $this->emptyInternalValues();
        SortingLogic::sortTasks($tasks, $this->getConfig('sorting_logic'));

        // phase 1:
        // get times of tasks, update it maybe by timetagger,
        // add tasks as reference to internal attributes.
        foreach ($tasks as $i => &$task) {

            // getting the tasks subtasks
            if (array_key_exists($task['id'], $subtasks_by_task_id)) {
                $subtasks = $subtasks_by_task_id[$task['id']];
            } else {
                $subtasks = [];
            }

            // Calculating the spicey stuff: the task TIMES !!
            $calculator = new TimesCalculator(
                $task,
                $subtasks,
                [
                    'non_time_mode_minutes' => $this->getConfig('non_time_mode_minutes')
                ],
                $this->getTimetaggerTranscriber()
            );
            $estimated = $calculator->getEstimated();
            $spent = $calculator->getSpent();
            $remaining = $calculator->getRemaining();
            $overtime = $calculator->getOvertime();
            // Also modify the original task accordingly. This will
            // extend certain keys, which did not exist before that,
            // but are maybe used later somewhere.
            $calculator->updateTask($task);

            // maybe overwrite the spent time, though, by the
            // TimetaggerTranscriber
            $this->overwriteTimes($task);

            // add the tasks to some internal variables (by reference)
            $this->tasks[$task['id']] = &$tasks[$i];
            $this->addTaskToLevel($tasks[$i]);

            // store some project related stuff as well
            $this->addProjectInfo($task);
        }
        unset($task);

        // phase 2:
        // update the remaining tasks with timetagger, if
        // it's enabled and such tasks exist
        $this->overwriteTimesFinal();

        // phase 3:
        // now use the final updated tasks to add them into
        // internal time attributes
        foreach ($tasks as $i => $task) {
            $estimated = $task['time_estimated'];
            $spent = $task['time_spent'];
            $remaining = $task['time_remaining'];
            $overtime = $task['time_overtime'];

            $this->times->addTimes($estimated, $spent, $remaining, $overtime);
            $this->times_by_swimlane->addTimes($estimated, $spent, $remaining, $overtime, $task['swimlane_name']);
            $this->times_by_column->addTimes($estimated, $spent, $remaining, $overtime, $task['column_name']);
            $this->times_by_swimlane_column->addTimes($estimated, $spent, $remaining, $overtime, $task['swimlane_name'] . $task['column_name']);
            $this->addTimesLevelDepending($estimated, $spent, $remaining, $overtime, $task);
            $this->times_by_project->addTimes($estimated, $spent, $remaining, $overtime, $task['project_id']);
            $this->times_by_task->addTimes($estimated, $spent, $remaining, $overtime, $task['id']);
            $this->times_by_user->addTimes($estimated, $spent, $remaining, $overtime, $task['owner_id']);
        }
        unset($task);

        $this->sortProjects();
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
     * Checks whether the internal config has enough values
     * set in the config to initialize the TimetaggerTranscriber.
     *
     * @return boolean
     */
    public function isTimetaggerConfigSet()
    {
        return (
            $this->getConfig('timetagger_url') != ''
            && $this->getConfig('timetagger_authtoken') != ''
            && $this->getConfig('timetagger_start_fetch') != ''
        );
    }

    /**
     * Get the internal config for the given key.
     *
     * @param  string $key
     * @return string|integer|array|null
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
     * Get estimated by column.
     *
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByColumn($column = '', $readable = false)
    {
        return $this->times_by_column->getEstimated($column, $readable);
    }

    /**
     * Get estimated by level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByLevel($level = '', $readable = false)
    {
        return $this->times_by_level->getEstimated($level, $readable);
    }

    /**
     * Get estimated by project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByProject($project_id = -1, $readable = false)
    {
        return $this->times_by_project->getEstimated($project_id, $readable);
    }

    /**
     * Get estimated by project on home.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByProjectHome($project_id = -1, $readable = false)
    {
        return $this->times_by_project_home->getEstimated($project_id, $readable);
    }

    /**
     * Get estimated by project + level.
     *
     * @param  integer $project_id
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByProjectLevel($project_id = -1, $level = '', $readable = false)
    {
        return $this->times_by_project_level->getEstimated($project_id . $level, $readable);
    }

    /**
     * Get estimated by swimlane.
     *
     * @param  string $swimlane
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedBySwimlane($swimlane = '', $readable = false)
    {
        return $this->times_by_swimlane->getEstimated($swimlane, $readable);
    }

    /**
     * Get estimated by swimlane+column.
     *
     * @param  string $swimlane
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedBySwimlaneColumn($swimlane = '', $column = '', $readable = false)
    {
        $entity = $swimlane . $column;
        return $this->times_by_swimlane_column->getEstimated($entity, $readable);
    }

    /**
     * Get estimated by task.
     *
     * @param  integer $task_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByTask($task_id = -1, $readable = false)
    {
        return $this->times_by_task->getEstimated($task_id, $readable);
    }

    /**
     * Get estimated by user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getEstimatedByUser($user_id = -1, $readable = false)
    {
        return $this->times_by_user->getEstimated($user_id, $readable);
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
     * Get overtime by column.
     *
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByColumn($column = '', $readable = false)
    {
        return $this->times_by_column->getOvertime($column, $readable);
    }

    /**
     * Get overtime by level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByLevel($level = '', $readable = false)
    {
        return $this->times_by_level->getOvertime($level, $readable);
    }

    /**
     * Get overtime by project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByProject($project_id = -1, $readable = false)
    {
        return $this->times_by_project->getOvertime($project_id, $readable);
    }

    /**
     * Get overtime by project on home.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByProjectHome($project_id = -1, $readable = false)
    {
        return $this->times_by_project_home->getOvertime($project_id, $readable);
    }

    /**
     * Get overtime by project + level.
     *
     * @param  integer $project_id
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByProjectLevel($project_id = -1, $level = '', $readable = false)
    {
        return $this->times_by_project_level->getOvertime($project_id . $level, $readable);
    }

    /**
     * Get overtime by swimlane.
     *
     * @param  string $swimlane
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeBySwimlane($swimlane = '', $readable = false)
    {
        return $this->times_by_swimlane->getOvertime($swimlane, $readable);
    }

    /**
     * Get overtime by swimlane+column.
     *
     * @param  string $swimlane
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeBySwimlaneColumn($swimlane = '', $column = '', $readable = false)
    {
        $entity = $swimlane . $column;
        return $this->times_by_swimlane_column->getOvertime($entity, $readable);
    }

    /**
     * Get overtime by task.
     *
     * @param  integer $task_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByTask($task_id = -1, $readable = false)
    {
        return $this->times_by_task->getOvertime($task_id, $readable);
    }

    /**
     * Get overtime by user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getOvertimeByUser($user_id = -1, $readable = false)
    {
        return $this->times_by_user->getOvertime($user_id, $readable);
    }

    /**
     * Get percent of times by project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @param  string  $suffix
     * @return float|string
     */
    public function getPercentByProject($project_id = -1, $readable = false, $suffix = '%')
    {
        return $this->times_by_project->getPercent($project_id, $readable, $suffix);
    }

    /**
     * Get percent of times by project on home.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @param  string  $suffix
     * @return float|string
     */
    public function getPercentByProjectHome($project_id = -1, $readable = false, $suffix = '%')
    {
        return $this->times_by_project_home->getPercent($project_id, $readable, $suffix);
    }

    /**
     * Get percent of times by task.
     *
     * @param  integer $task_id
     * @param  boolean $readable
     * @param  string  $suffix
     * @return float|string
     */
    public function getPercentByTask($task_id = -1, $readable = false, $suffix = '%')
    {
        return $this->times_by_task->getPercent($task_id, $readable, $suffix);
    }

    /**
     * Get the project ids from the internal times_by_project
     * attribute, which also should cover the correct sorting,
     * liek defined in the config.
     *
     * @return array
     */
    public function getProjectIds()
    {
        return $this->times_by_project->getEntities();
    }

    /**
     * Get the project ids by level, if this level exists internally.
     *
     * @param  string  $level
     * @return array
     */
    public function getProjectIdsByLevel($level)
    {
        return $this->project_ids_by_level[$level] ?? [];
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
     * Get remaining by column.
     *
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByColumn($column = '', $readable = false)
    {
        return $this->times_by_column->getRemaining($column, $readable);
    }

    /**
     * Get remaining by level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByLevel($level = '', $readable = false)
    {
        return $this->times_by_level->getRemaining($level, $readable);
    }

    /**
     * Get remaining by project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByProject($project_id = -1, $readable = false)
    {
        return $this->times_by_project->getRemaining($project_id, $readable);
    }

    /**
     * Get remaining by project on home.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByProjectHome($project_id = -1, $readable = false)
    {
        return $this->times_by_project_home->getRemaining($project_id, $readable);
    }

    /**
     * Get remaining by project + level.
     *
     * @param  integer $project_id
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByProjectLevel($project_id = -1, $level = '', $readable = false)
    {
        return $this->times_by_project_level->getRemaining($project_id . $level, $readable);
    }

    /**
     * Get remaining by swimlane.
     *
     * @param  string $swimlane
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingBySwimlane($swimlane = '', $readable = false)
    {
        return $this->times_by_swimlane->getRemaining($swimlane, $readable);
    }

    /**
     * Get remaining by swimlane+column.
     *
     * @param  string $swimlane
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingBySwimlaneColumn($swimlane = '', $column = '', $readable = false)
    {
        $entity = $swimlane . $column;
        return $this->times_by_swimlane_column->getRemaining($entity, $readable);
    }

    /**
     * Get remaining by task.
     *
     * @param  integer $task_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByTask($task_id = -1, $readable = false)
    {
        return $this->times_by_task->getRemaining($task_id, $readable);
    }

    /**
     * Get remaining by user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getRemainingByUser($user_id = -1, $readable = false)
    {
        return $this->times_by_user->getRemaining($user_id, $readable);
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
     * Get spent by column.
     *
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByColumn($column = '', $readable = false)
    {
        return $this->times_by_column->getSpent($column, $readable);
    }

    /**
     * Get spent by level.
     *
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByLevel($level = '', $readable = false)
    {
        return $this->times_by_level->getSpent($level, $readable);
    }

    /**
     * Get spent by project.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByProject($project_id = -1, $readable = false)
    {
        return $this->times_by_project->getSpent($project_id, $readable);
    }

    /**
     * Get spent by project on home.
     *
     * @param  integer $project_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByProjectHome($project_id = -1, $readable = false)
    {
        return $this->times_by_project_home->getSpent($project_id, $readable);
    }

    /**
     * Get spent by project + level.
     *
     * @param  integer $project_id
     * @param  string $level
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByProjectLevel($project_id = -1, $level = '', $readable = false)
    {
        return $this->times_by_project_level->getSpent($project_id . $level, $readable);
    }

    /**
     * Get spent by swimlane.
     *
     * @param  string $swimlane
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentBySwimlane($swimlane = '', $readable = false)
    {
        return $this->times_by_swimlane->getSpent($swimlane, $readable);
    }

    /**
     * Get spent by swimlane+column.
     *
     * @param  string $swimlane
     * @param  string $column
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentBySwimlaneColumn($swimlane = '', $column = '', $readable = false)
    {
        $entity = $swimlane . $column;
        return $this->times_by_swimlane_column->getSpent($entity, $readable);
    }

    /**
     * Get spent by task.
     *
     * @param  integer $task_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByTask($task_id = -1, $readable = false)
    {
        return $this->times_by_task->getSpent($task_id, $readable);
    }

    /**
     * Get spent by user.
     *
     * @param  integer $user_id
     * @param  boolean $readable
     * @return float|string
     */
    public function getSpentByUser($user_id = -1, $readable = false)
    {
        return $this->times_by_user->getSpent($user_id, $readable);
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
     * Get the internal tasks_by_level array. A level
     * can be defined as a parameter so that only that
     * level's task will be returned.
     *
     * @param string $level
     * @return array
     */
    public function getTasksByLevel($level = '')
    {
        if (array_key_exists($level, $this->tasks_by_level)) {
            return $this->tasks_by_level[$level];
        } else {
            return $this->tasks_by_level;
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
     * Check if TimetaggerTranscriber is available,
     * then overwrite the tasks spent time with the
     * logic of TimetaggerTranscriber and return
     * the new spent time.
     *
     * @param  array &$task
     */
    public function overwriteTimes(&$task)
    {
        if ($this->isTimetaggerConfigSet()) {
            if ($task['timetagger_can_overwrite']) {
                $this->getTimetaggerTranscriber()->overwriteTimesForTask($task);
            }
        }
    }

    /**
     * Check if TimetaggerTranscriber is available
     * and only then use the second phase for
     * overwriting tasks spent time.
     */
    public function overwriteTimesFinal()
    {
        if ($this->isTimetaggerConfigSet()) {
            $this->getTimetaggerTranscriber()->overwriteTimesForRemainingTasks();
        }
    }

    /**
     * Sort the projects with the wanted sorting logic.
     * This is, by now, only used for output in tooltips.
     */
    protected function sortProjects()
    {
        // first sort the TimesDataByEntity instance
        // for times_by_project, which will manage to
        // sort the projects by their time
        $project_sorting = $this->getConfig('project_sorting');
        if ($project_sorting == 'id') {
            $this->times_by_project->sort();
        } elseif ($project_sorting == 'remaining_hours_asc') {
            $this->times_by_project->sort('remaining', 'asc');
        } elseif ($project_sorting == 'remaining_hours_desc') {
            $this->times_by_project->sort('remaining', 'desc');
        }

        // now use this sorting to sort the plain IDs as well accordingly
        // 1. doing this by creating a lookup map first
        $sort_map = [];
        foreach ($this->times_by_project->getEntities() as $i => $id) {
            $sort_map[$id] = $i;
        }

        // 2. using this to sort the project_ids, while non existing ids will be
        //    put at the end
        foreach ($this->project_ids_by_level as &$project_ids) {
            self::sortProjectIdsLevel($project_ids, $sort_map);
        }
    }

    /**
     * Sort the given array by the sort_map, which should contain the project
     * id as the key and the sorting integer as the value.
     *
     * @param  array &$arr
     * @param  array $sort_map
     */
    protected static function sortProjectIdsLevel(&$arr, $sort_map)
    {
        usort($arr, function($a, $b) use ($sort_map) {
            $pa = isset($sort_map[$a]) ? $sort_map[$a] : PHP_INT_MAX;
            $pb = isset($sort_map[$b]) ? $sort_map[$b] : PHP_INT_MAX;
            if ($pa === $pb) {
                return 0; // behalte relative Reihenfolge; alternativ: return $a <=> $b;
            }
            return $pa < $pb ? -1 : 1;
        });
    }
}
