<?php

/**
 * This class is some kind of repository for all the
 * ProjectQuota instances for all the projects.
 * It also has the functionality to deplete certain
 * project quotas by a specific logic.
 *
 * In general it is used finally by TasksPlan for the
 * logic, whether a task can be planned or not.
 */

namespace Kanboard\Plugin\WeekHelper\Model;


class ProjectQuotaAll
{
    /**
     * Project quotas by project id:
     *
     *  [
     *      project_id => ProjectQuota
     *  ]
     *
     * @var ProjectQuota[]
     **/
    var $project_quotas = [];

    /**
     * Initialize a ProjectQuota for the given project_id.
     * Returns false, if it already exists or something went
     * wrong.
     *
     * @param  integer $project_id
     * @param  array $project_info
     * @return boolean
     */
    public function initProjectQuota($project_id, $project_info = [])
    {
        if (array_key_exists($project_id, $this->project_quotas)) {
            return false;
        }
        $this->project_quotas[$project_id] = new ProjectQuota($project_info);
        return true;
    }

    /**
     * Get the quota in minutes by the given project id
     * and day.
     *
     * Returns -1 if something went wrong or if it does not exist.
     *
     * @param  integer $project_id
     * @param  string $day
     * @return integer
     */
    public function getQuotaByProjectIdAndDay($project_id, $day)
    {
        if (!array_key_exists($project_id, $this->project_quotas)) {
            return -1;
        }
        return $this->project_quotas[$project_id]->getQuota($day);
    }

    /**
     * Substract quota for the given project and day by the given
     * amount of minutes.
     *
     * @param  integer $project_id
     * @param  string $day
     * @param  integer $minutes
     * @return boolean
     */
    public function substractQuotaByProjectIdAndDay($project_id, $day, $minutes)
    {
        if (!array_key_exists($project_id, $this->project_quotas)) {
            return false;
        }
        return $this->project_quotas[$project_id]->substractQuota($day, $minutes) != -1;
    }
}
