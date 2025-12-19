<?php

/**
 * This class basically holds just the info about the time
 * limits a project can have with the proejct info meta
 * data keys: "project_max_hours_day" and the key
 * "project_max_hours_block".
 */

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


class ProjectConditions
{
    /**
     * Similar like for block, but for days and without the
     * slot_key aspect.
     *
     * @var array
     **/
    var $planned_day = [];

    /**
     * Similar like block, but for days and thus without the
     * slot_key aspect.
     *
     * @param integer $project_id
     * @param string $day
     * @param integer $time
     */
    public function addTimeToDay($project_id, $day, $time)
    {
        if (!array_key_exists($project_id, $this->planned_day)) {
            $this->planned_day[$project_id] = [
                'mon' => 0,
                'tue' => 0,
                'wed' => 0,
                'thu' => 0,
                'fri' => 0,
                'sat' => 0,
                'sun' => 0,
                'overflow' => 0,
            ];
        }
        $this->planned_day[$project_id][$day] += $time;
    }

    /**
     * Return the planned time for the whole day for the project.
     *
     * @param  integer $project_id
     * @param  string $day
     */
    public function getPlannedTimeForDay($project_id, $day)
    {
        if (!array_key_exists($project_id, $this->planned_day)) {
            // basically initialize some kind of empty day-time counter
            // here, if the given id did not exist.
            $this->addTimeToDay($project_id, $day, 0);
        }
        return $this->planned_day[$project_id][$day];
    }

    /**
     * Check if the daily limit for the given task is full. A task
     * array will also hold the "project_max_hours_day" value so
     * that I can check on this key. This method will return the
     * remaining contingent available for the project.
     *
     * @param  array $task
     * @param  string $day
     * @return integer
     */
    public function getLeftDailyTime($task, $day)
    {
        $project_daily_limit = TimeHelper::hoursToMinutes($task['project_max_hours_day']);
        $project_id = $task['project_id'];
        return $project_daily_limit - $this->getPlannedTimeForDay($project_id, $day);
    }
}
