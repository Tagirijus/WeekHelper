<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;
use Kanboard\Model\ProjectModel;
use Kanboard\Plugin\WeekHelper\Helper\ProjectInfoParser;
use Kanboard\Plugin\WeekHelper\Helper\TaskInfoParser;
use Kanboard\Plugin\WeekHelper\Helper\SortingLogic;
use Kanboard\Plugin\WeekHelper\Helper\DistributionLogic;
use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


class AutomaticPlanner extends Base
{
    /**
     * All active projects, parsed with their additional meta data.
     *
     * @var array
     **/
    var $projects = null;

    /**
     * The raw config for the sorting logic. It's a cache variable
     * so that for getting this config no DB query has to be done
     * everytime.
     *
     * @var string
     **/
    var $sorting_logic_config = null;

    /**
     * The raw config for the time slots / distribution logic.
     * It is an array with the weekdays (short) as key and the
     * raw config string from the user.
     *
     * @var array
     **/
    var $distribution_config = null;

    /**
     * Cache variable for the hours view helper output of the
     * project times getter method.
     *
     * @var array
     **/
    var $project_times = null;

    /**
     * The cache variable for the tasks of the active week.
     *
     * @var array
     **/
    var $tasks_active_week = null;

    /**
     * The cache variable for the tasks of the planned week.
     *
     * @var array
     **/
    var $tasks_planned_week = null;

    /**
     * The TasksPlan instance for the active week.
     *
     * @var TasksPlan
     **/
    var $tasks_plan_active_week = null;

    /**
     * The TasksPlan instance for the planned week.
     *
     * @var TasksPlan
     **/
    var $tasks_plan_planned_week = null;

    /**
     * Get (and if needed first initialize it) the internal projects
     * array with the active projects and their additional meta data.
     *
     * @return array
     */
    public function getProjects()
    {
        if (is_null($this->projects)) {
            $this->initProjects();
        }
        return $this->projects;
    }

    /**
     * Initialize all active projects and parse their additional
     * meta data. Store it in the internal attribute.
     */
    private function initProjects()
    {
        $projects = $this->projectModel->getAllByStatus(ProjectModel::ACTIVE);
        $projects = array_column($projects, null, 'id');
        foreach ($projects as $project_id => $project) {
            $info = ProjectInfoParser::getProjectInfoByProject($project);
            $projects[$project_id]['info'] = $info;
        }
        $this->projects = $projects;
    }

    /**
     * Get (and if needed first initialize it) the internal sorting
     * logic config array.
     *
     * @return array
     */
    public function getSortingLogicConfig()
    {
        if (is_null($this->sorting_logic_config)) {
            $this->initSortingLogicConfig();
        }
        return $this->sorting_logic_config;
    }

    /**
     * Initialize the sorting logic config and store it in the
     * internal attribute for this value.
     */
    private function initSortingLogicConfig()
    {
        $this->sorting_logic_config = $this->configModel->get('weekhelper_sorting_logic', '');
    }

    /**
     * Get (and if needed first initialize it) the internal distribution
     * config array.
     *
     * @return array
     */
    public function getDistributionConfig()
    {
        if (is_null($this->distribution_config)) {
            $this->initDistributionConfig();
        }
        return $this->distribution_config;
    }

    /**
     * Initialize the distribution config and store it in the
     * internal attribute for this value.
     */
    private function initDistributionConfig()
    {
        $this->distribution_config = [
            'mon' => $this->configModel->get('weekhelper_monday_slots', ''),
            'tue' => $this->configModel->get('weekhelper_tuesday_slots', ''),
            'wed' => $this->configModel->get('weekhelper_wednesday_slots', ''),
            'thu' => $this->configModel->get('weekhelper_thursday_slots', ''),
            'fri' => $this->configModel->get('weekhelper_friday_slots', ''),
            'sat' => $this->configModel->get('weekhelper_saturday_slots', ''),
            'sun' => $this->configModel->get('weekhelper_sunday_slots', ''),
            'min_slot_length' => $this->configModel->get('weekhelper_minimum_slot_length', 0),
        ];
    }

    /**
     * Get (and if needed first initialize it) the internal array
     * for the project times; the output of the hours view method.
     *
     * @return array
     */
    public function getProjectTimes()
    {
        if (is_null($this->project_times)) {
            $this->initProjectTimes();
        }
        return $this->project_times;
    }

    /**
     * Initialize the tasks of the active week. Also attach the
     * projects metadata to each task for easier access later on.
     */
    private function initProjectTimes()
    {
        $this->project_times = $this->helper->hoursViewHelper->getTimesForAllActiveProjects();
    }

    /**
     * Get (and if needed first initialize it) the internal array
     * for the tasks of the active week.
     *
     * @return array
     */
    public function getTasksActiveWeek()
    {
        if (is_null($this->tasks_active_week)) {
            $this->initTasksActiveWeek();
        }
        return $this->tasks_active_week;
    }

    /**
     * Initialize the tasks of the active week. Also attach the
     * projects metadata to each task for easier access later on.
     */
    private function initTasksActiveWeek()
    {
        $level_active_week = $this->configModel->get('weekhelper_level_active_week', '');
        // the project_times variable has to be initiated as well;
        // so I will just call the getter, which will initiliaze it, if needed
        $this->getProjectTimes();
        if (array_key_exists($level_active_week, $this->helper->hoursViewHelper->tasks_per_level)) {
            $this->tasks_active_week = $this->helper->hoursViewHelper->tasks_per_level[$level_active_week];
        } else {
            $this->logger->info(
                'AutomaticPlanner->initTasksActiveWeek() cannot access '
                . '"' . $level_active_week . '" '
                . 'in "' . $this->helper->hoursViewHelper->tasks_per_level . '"'
            );
        }

        // also extend the project array to each task
        $this->extendTasksArray($this->tasks_active_week);
    }

    /**
     * Extend the given tasks array with the soem internal
     * or "still to parse" data. This will extend the basic
     * Kanboard tasks array. It will be given an array of
     * Kanboard task arrays. Each of this Kanboard arrays
     * will be extended.
     *
     * @param  array  &$tasks
     */
    public function extendTasksArray(&$tasks = [])
    {
        $projects = $this->getProjects();
        foreach ($tasks as $key => $task) {
            // if I need other (native Kanboard) project values to sort on,
            // I should add them here. Otherwise with just the key "info"
            // there are the ones added / parsed by my plugin.
            $tasks[$key] = array_merge($task, $projects[$task['project_id']]['info']);

            // also a task can have certain values given in the description text, which
            // can be parsed into task array keys. e.g. "project_type" can be overwritten
            // here
            TaskInfoParser::extendTask($tasks[$key]);
        }
    }

    /**
     * Get (and if needed first initialize it) the internal array
     * for the tasks of the planned week.
     *
     * @return array
     */
    public function getTasksPlannedWeek()
    {
        if (is_null($this->tasks_planned_week)) {
            $this->initTasksPlannedWeek();
        }
        return $this->tasks_planned_week;
    }

    /**
     * Initialize the tasks of the active week. Also attach the
     * projects metadata to each task for easier access later on.
     */
    private function initTasksPlannedWeek()
    {
        $level_planned_week = $this->configModel->get('weekhelper_level_planned_week', '');
        // the project_times variable has to be initiated as well;
        // so I will just call the getter, which will initiliaze it, if needed
        $this->getProjectTimes();
        if (array_key_exists($level_planned_week, $this->helper->hoursViewHelper->tasks_per_level)) {
            $this->tasks_planned_week = $this->helper->hoursViewHelper->tasks_per_level[$level_planned_week];
        } else {
            $this->logger->info(
                'AutomaticPlanner->initTasksPlannedWeek() cannot access '
                . '"' . $level_planned_week . '" '
                . 'in "' . $this->helper->hoursViewHelper->tasks_per_level . '"'
            );
        }

        // also extend the project info to each task
        $this->extendTasksArray($this->tasks_planned_week);
    }

    /**
     * This is the most important output. This method basically will get
     * all other "getAutomaticPlan..." methods their base to work with.
     * It is an array, which holds the week plan and further data.
     *
     * The array structure is:
     *     [
     *         'active' => [
     *             'mon' => [array with sorted tasks],
     *             'tue' => [array with sorted tasks],
     *             ...
     *             'sun' => [array with sorted tasks],
     *             'overflow' => [array with sorted tasks]
     *         ],
     *         'planned' => [
     *             'mon' => [array with sorted tasks],
     *             'tue' => [array with sorted tasks],
     *             ...
     *             'sun' => [array with sorted tasks],
     *             'overflow' => [array with sorted tasks]
     *         ],
     *         maybe more data here later ...
     *     ]
     *
     * @return array
     */
    public function getAutomaticPlanAsArray()
    {
        return [
            'active' => $this->getTasksPlanActiveWeek()->getPlan(),
            'planned' => $this->getTasksPlanPlannedWeek()->getPlan(),
        ];
    }

    /**
     * Instantiate the internal TasksPlan instances.
     */
    public function initTasksPlans()
    {
        $this->tasks_plan_active_week = $this->prepareWeek($this->getTasksActiveWeek());
        $this->tasks_plan_planned_week = $this->prepareWeek($this->getTasksPlannedWeek(), true);
    }

    /**
     * Return the internal tasks plan for the week:
     * "active" or "planned". It will also first
     * initialize the internal variables, if needed.
     *
     * @param string  $week
     * @return TasksPlan
     */
    public function getTasksPlan($week = 'active')
    {
        // check if null; both will be initialized then
        if (is_null($this->tasks_plan_active_week)) {
            $this->initTasksPlans();
        }
        if ($week == 'active') {
            return $this->tasks_plan_active_week;
        } else {
            return $this->tasks_plan_planned_week;
        }
    }

    /**
     * Prepare an active week with the given tasks
     * and return its TasksPlan instance.
     *
     * @param  array $tasks
     * @param  boolean $ignore_now
     * @return TasksPlan
     */
    public function prepareWeek($tasks, $ignore_now = false)
    {
        $sorted_tasks = SortingLogic::sortTasks(
            $tasks,
            $this->getSortingLogicConfig()
        );

        $distributor = new DistributionLogic($this->getDistributionConfig());
        if (!$ignore_now) {
            $distributor->updateWorkedTimesForTasksPlan($sorted_tasks);
            $distributor->depleteUntilNow();
        }
        $distributor->distributeTasks($sorted_tasks);
        $tasks_plan = $distributor->getTasksPlan();

        return $tasks_plan;
    }

    /**
     * Get the automatic plan as plaintext.
     *
     * The $params parameter can hold the following
     * options, which would be basically parameters
     * themself. But for better extending I made this
     * one an array:
     *
     *     week_only:
     *         - '': both weeks
     *         - 'active': only active week
     *         - 'planned': only planned / next week
     *
     *     days:
     *         A string containing the abbreviation
     *         weekdays which should be shown. Can
     *         be comma separated. String will just
     *         be checked with "str_contains()" later.
     *         BUT: it can also contain numbers, which
     *         stand for the relation to "today". So it
     *         can contain "0" and stand for "today". Or
     *         "-1" which stand for "yesterday". This way
     *         it should be possible to define days
     *         dynamically.
     *
     *     hide_times:
     *         If true the day times will be hidden.
     *
     *     hide_length:
     *         If true the task length will be hidden.
     *
     *     hide_task_title:
     *         If true, it hides the original task title.
     *
     *     prepend_project_name:
     *         If true, it prepends the project name.
     *
     *     prepend_project_alias:
     *         If true, it prepend the project alias.
     *
     *     show_day_planned:
     *         If true, show the times for a whole day.
     *
     *     show_week_times:
     *         If true, shows time stats for the week.
     *
     * @param  array $params See docstring for info
     * @return string
     */
    public function getAutomaticPlanAsText($params = [])
    {
        // params preparation
        $week_only = $params['week_only'] ?? '';
        $show_week_times = $params['show_week_times'] ?? false;

        if ($week_only == 'active' || $week_only == '') {
            // both weeks are needed, thus also a title for
            // each week to distinguish both
            if ($week_only == '') {
                $out = "ACTIVE WEEK\n";
                if ($show_week_times) {
                    $out .= $this->prepareWeekTimesString('active') . "\n";
                }
                $out .= "\n";
            } elseif ($show_week_times) {
                $out = $this->prepareWeekTimesString('active') . "\n\n";
            }
            $this->formatSinglePlaintextDays(
                $out,
                'active',
                $params
            );
        }

        if ($week_only == 'planned' || $week_only == '') {
            // both weeks are needed, thus also a title for
            // each week to distinguish both
            if ($week_only == '') {
                $out .= "\n\n";
                $out .= "PLANNED WEEK\n";
                if ($show_week_times) {
                    $out .= $this->prepareWeekTimesString('planned') . "\n";
                }
                $out .= "\n";
            } elseif ($show_week_times) {
                $out = $this->prepareWeekTimesString('planned') . "\n\n";
            }
            $this->formatSinglePlaintextDays(
                $out,
                'planned',
                $params
            );
        }

        return $out;
    }

    /**
     * Prepare the week times string for the given week,
     * which is "active" or "planned".
     *
     * @param  string $week
     * @return string
     */
    public function prepareWeekTimesString($week = 'active')
    {
        $remaining = $this->getTasksPlan($week)->getGlobalTimesForWeek()['remaining'];
        $spent = $this->getTasksPlan($week)->getGlobalTimesForWeek()['spent'];
        $planned = $this->getTasksPlan($week)->getGlobalTimesForWeek()['planned'];

        $out = 'Planned: ' . TimeHelper::minutesToReadable($planned, ' h');
        $out .= ' (Remaining: ' . TimeHelper::minutesToReadable($remaining, ' h');
        $out .= ', Spent: ' . TimeHelper::minutesToReadable($spent, ' h');
        $out .= ')';
        return $out;
    }

    /**
     * Extend the $out string with the given planned week,
     * which is either the active one or the planned one.
     *
     * @param  string &$out
     * @param  string $week
     * @param  array $params See getAutomaticPlanAsText() for info
     */
    public function formatSinglePlaintextDays(
        &$out,
        $week,
        $params = []
    )
    {
        // with this variable I can have better visual breakpoints so that it
        // is more clear where whole work blocks are being separated
        $last_time = 0;

        // params preparation
        $days = $params['days'] ?? 'mon,tue,wed,thu,fri,sat,sun,overflow,ovr';

        foreach ($this->getTasksPlan($week)->getPlan() as $day => $tasks) {
            if (
                str_contains($days, $day)
                || (
                    str_contains($days, 'ovr')
                    && $day == 'overflow'
                )
                ||
                str_contains($days, TimeHelper::diffOfWeekDays('', $day))
            ) {
                // prepare the day times string
                $day_times = $params['show_day_planned'] ?? false;
                if ($day_times) {
                    $day_times = (
                        TimeHelper::minutesToReadable(
                            $this->getTasksPlan($week)->getGlobalTimesForDay($day)['planned'],
                            'h'
                        )
                    );
                } else {
                    $day_times = '';
                }

                // print out day name, if there are probably more
                // than one day wanted
                if (str_contains($days, ',')) {
                    $out .= strtoupper($day) . ($day_times ? " ($day_times)" : '') . ":\n";

                // probably just one day; then maybe at least
                // print out the day time stats if wanted
                } else {
                    $out .= ($day_times ? "$day_times:\n" : '');
                }

                foreach ($tasks as $task) {
                    if ($last_time == 0) {
                        $last_time = $task['end'];
                    } else {
                        if ($task['start'] - $last_time > 1) {
                            $out .= "\n";
                        }
                        $last_time = $task['end'];
                    }
                    $out .= $this->formatSinglePlaintextTask(
                        $task,
                        $params
                    );
                }
                $out .= "\n\n";
            }
        }
    }

    /**
     * Format teh given task into a predefined line, which shall
     * represent a single task.
     *
     * Maybe one day it might even be configurable via the settings.
     *
     * @param  array $task
     * @param  array $params See getAutomaticPlanAsText() for info
     * @return string
     */
    public function formatSinglePlaintextTask($task, $params = [])
    {
        $out = '';
        $start_daytime = TimeHelper::minutesToReadable($task['start']);
        $end_daytime = TimeHelper::minutesToReadable($task['end']);
        $length = TimeHelper::minutesToReadable($task['length'], ' h');

        // time of day
        if (!($params['hide_times'] ?? false)) {
            $out .= (
                str_pad($start_daytime, 5, " ", STR_PAD_LEFT)
                . ' - '
                . str_pad($end_daytime, 5, " ", STR_PAD_LEFT)
            );
            $out .= '  ';
        }

        // task title
        $out .= $this->formatSinglePlaintextTitle($task, $params);

        // length of task
        if (!($params['hide_length'] ?? false)) {
            $out .= " (" . $length . ")";
        }
        $out .= "\n";

        return $out;
    }

    /**
     * Format a single plaintext title.
     *
     * TODO:
     * Later I might consider making this whole formatting
     * logic to set via the config by the user. Maybe with
     * some tiny templater language.
     * On the other site this way it is choosable what to
     * show from the URI ... this is also good and flexible.
     * I will see!
     *
     * @param  array $task
     * @param  array $params See getAutomaticPlanAsText() for info
     * @return string
     */
    public function formatSinglePlaintextTitle($task, $params = [])
    {
        $title = $params['hide_task_title'] ?? false ? '' : $task['task']['title'];

        $title = (
            $params['prepend_project_name'] ?? false ?
            $task['task']['project_name'] . ': ' . $title
            : $title
        );

        $title = (
            $params['prepend_project_alias'] ?? false ?
            (
                $task['task']['project_alias'] != '' ?
                '[' . $task['task']['project_alias'] . '] ' . $title
                : $title
            )
            : $title
        );

        return $title;
    }
}
