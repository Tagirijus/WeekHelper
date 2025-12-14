<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;


class AutomaticPlanner extends Base
{
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
        return [];
    }

    /**
     * Get the automatic plan as plaintext.
     *
     * @return string
     */
    public function getAutomaticPlanAsText()
    {
        // So kann ich hier Config abrufen:
        // $stick_enabled = $this->configModel->get('weekhelper_automatic_planner_sticky_enabled', 1);

        // $project_times = $this->helper->hoursViewHelper->getTimesForAllActiveProjects();
        // $this->logger->info(json_encode($project_times));
        // $this->logger->info(json_encode($this->helper->hoursViewHelper->tasks_per_level));

        // so bekomme ich Projekt Metadatan per ProjectID
        // $project_info = $this->helper->projectInfoParser->getProjectInfoById(11);
        // $this->logger->info(json_encode($project_info));

        return 'TODO';
    }
}
