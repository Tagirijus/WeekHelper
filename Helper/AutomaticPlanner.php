<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;


class AutomaticPlanner extends Base
{

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
