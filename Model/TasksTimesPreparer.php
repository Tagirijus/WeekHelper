<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Model\SortingLogic;
use Kanboard\Plugin\WeekHelper\Model\TaskDataExtender;
use Kanboard\Plugin\WeekHelper\Model\TimesCalculator;
use Kanboard\Plugin\WeekHelper\Model\TimesData;
use Kanboard\Plugin\WeekHelper\Model\TimesDataPerEntity;
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
        // project_sorting can be:
        //    'id', 'remaining_hours_asc', 'remaining_hours_desc'
        'project_sorting' => 'id',
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
     * Add the given task to the internal tasks_per_level array, depending
     * on it's level. This info at this point should already be parsed
     * be the TaskDataExtender, thus the key 'levels' should exist.
     *
     * @param array &$task
     */
    public function addTaskToLevel(&$task)
    {
        foreach (($task['levels'] ?? []) as $level) {
            $this->tasks_per_level[$level][$task['id']] = &$task;
        }
    }

    /**
     * Add the given times to the internal times_per_level array,
     * depending on the tasks level. This info at this point should
     * already be parsed be the TaskDataExtender, thus the key
     * 'levels' should exist.
     *
     * @param float $estimated]
     * @param float $spent
     * @param float $remaining
     * @param float $overtime
     * @param array $task
     */
    public function addTimesToLevel(
        $estimated,
        $spent,
        $remaining,
        $overtime,
        $task
    )
    {
        foreach (($task['levels'] ?? []) as $level) {
            $this->times_per_level->addTimes(
                $estimated, $spent, $remaining, $overtime, $level
            );
        }
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
            TaskDataExtender::extendTask($task, $this->getConfig('levels_config'));
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
     * Get has_times per project.
     *
     * @param  integer $project_id
     * @return boolean
     */
    public function hasTimesPerProject($project_id = -1)
    {
        return $this->times_per_project->hasTimes($project_id);
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
     * @param  array  &$tasks
     * @param  array  $subtasks_by_task_id Key is task id and values are the subtasks
     */
    public function initTasksAndTimes(&$tasks = [], $subtasks_by_task_id = [])
    {
        $this->extendTasksData($tasks);
        $this->emptyInternalValues();
        SortingLogic::sortTasks($tasks, $this->getConfig('sorting_logic'));

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

            // add the tasks to some internal variables (by reference)
            $this->tasks[$task['id']] = &$tasks[$i];
            $this->addTaskToLevel($tasks[$i]);

            // == == == == == == == ==
            // ADDING TIMES   -  START
            // == == == == == == == ==

            $this->times->addTimes($estimated, $spent, $remaining, $overtime);
            $this->addTimesToLevel($estimated, $spent, $remaining, $overtime, $task);
            $this->times_per_project->addTimes($estimated, $spent, $remaining, $overtime, $task['project_id']);
            $this->times_per_user->addTimes($estimated, $spent, $remaining, $overtime, $task['owner_id']);

            // == == == == == == ==
            // ADDING TIMES  -  END
            // == == == == == == ==

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
     * Get the internal config for the given key.
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
     * Get the project ids from the internal times_per_project
     * attribute, which also should cover the correct sorting,
     * liek defined in the config.
     *
     * @return array
     */
    public function getProjectIds()
    {
        return $this->times_per_level->getEntities();
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
     * Sort the projects with the wanted sorting logic.
     * This is, by now, only used for output in tooltips.
     */
    protected function sortProjects()
    {
        $project_sorting = $this->getConfig('project_sorting');

        if ($project_sorting == 'id') {
            $this->times_per_project->sort();
        } elseif ($project_sorting == 'remaining_hours_asc') {
            $this->times_per_project->sort('remaining', 'asc');
        } elseif ($project_sorting == 'remaining_hours_desc') {
            $this->times_per_project->sort('remaining', 'desc');
        }
    }
}
