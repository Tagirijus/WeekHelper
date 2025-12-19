<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;
use Kanboard\Model\ProjectModel;
use Kanboard\Plugin\WeekHelper\Helper\ProjectInfoParser;
use Kanboard\Plugin\WeekHelper\Helper\SortingLogic;
use Kanboard\Plugin\WeekHelper\Helper\DistributionLogic;


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
        $project_info_parser = new ProjectInfoParser;
        foreach ($projects as $project_id => $project) {
            $info = $project_info_parser->getProjectInfoByProject($project);
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
            'sun' => $this->configModel->get('weekhelper_sunday_slots', '')
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
        $projects = $this->getProjects();
        foreach ($this->tasks_active_week as $key => $task) {
            // if I need other (native Kanboard) project values to sort on,
            // I should add them here. Otherwise with just the key "info"
            // there are the ones added / parsed by my plugin.
            $this->tasks_active_week[$key] = array_merge($task, $projects[$task['project_id']]['info']);
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
        $projects = $this->getProjects();
        foreach ($this->tasks_planned_week as $key => $task) {
            // if I need other (native Kanboard) project values to sort on,
            // I should add them here. Otherwise with just the key "info"
            // there are the ones added / parsed by my plugin.
            $this->tasks_planned_week[$key] = array_merge($task, $projects[$task['project_id']]['info']);
        }
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
     *             'sun' => [array with sorted tasks]
     *         ],
     *         maybe more data here later ...
     *     ]
     *
     * @return array
     */
    public function getAutomaticPlanAsArray()
    {
        $active_week = $this->prepareWeek($this->getTasksActiveWeek());
        $planned_week = $this->prepareWeek($this->getTasksPlannedWeek());

        return [
            'active' => $active_week,
            'planned' => $planned_week,
        ];
    }

    /**
     * Prepare an active week with the given tasks.
     * This method gets an array of tasks, sorts them
     * with the sorter class, splits these task
     * over the time slots, defined in the config and
     * finally returns an array with the following
     * structure:
     *     [
     *         'mon' => [array with sorted tasks],
     *         'tue' => [array with sorted tasks],
     *         ...
     *         'sun' => [array with sorted tasks],
     *         'overflow' => [array with sorted tasks]
     *     ]
     *
     * @param  array $tasks
     * @return array
     */
    public function prepareWeek($tasks)
    {
        $sorter = new SortingLogic;
        $sorted_tasks = $sorter->sortTasks(
            $tasks,
            $this->getSortingLogicConfig()
        );

        $distributor = new DistributionLogic($this->getDistributionConfig());
        $distributor->distributeTasks($sorted_tasks);
        $distribution = $distributor->getTasksPlan();

        return $distribution;
    }

    /**
     * Get the automatic plan as plaintext.
     *
     * @return string
     */
    public function getAutomaticPlanAsText()
    {
        $final_plan = $this->getAutomaticPlanAsArray();
        $out = "active week\n";
        $out .= "\n";
        $out .= "monday tasks:\n";
        foreach ($final_plan['active']['mon'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "tuesday tasks:\n";
        foreach ($final_plan['active']['tue'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "wednesday tasks:\n";
        foreach ($final_plan['active']['wed'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "thursday tasks:\n";
        foreach ($final_plan['active']['thu'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "friday tasks:\n";
        foreach ($final_plan['active']['fri'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "saturday tasks:\n";
        foreach ($final_plan['active']['sat'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "sunday tasks:\n";
        foreach ($final_plan['active']['sun'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }
        $out .= "\n";
        $out .= "overflow tasks:\n";
        foreach ($final_plan['active']['overflow'] as $task) {
            $out .= $this->formatSinglePlaintextTask($task);
        }

        return $out;
    }

    /**
     * Format teh given task into a predefined line, which shall
     * represent a single task.
     *
     * Maybe one day it might even be configurable via the settings.
     *
     * @param  array $task
     * @return string
     */
    public function formatSinglePlaintextTask($task)
    {
        $out = '';
        $start_daytime = (
            (string) floor($task['start'] / 60)
            . ':'
            . (string) sprintf('%02d', round($task['start'] % 60))
        );
        $end_daytime = (
            (string) floor($task['end'] / 60)
            . ':'
            . (string) sprintf('%02d', round($task['end'] % 60))
        );
        $length = (
            (string) floor($task['length'] / 60)
            . ':'
            . (string) sprintf('%02d', round($task['length'] % 60))
        );

        $out .= $start_daytime . ' - ' . $end_daytime;
        $out .=  '  >  ' . $task['task']['title'];
        $out .= " (" . $length . " h)" . "\n";
        return $out;
    }
}
