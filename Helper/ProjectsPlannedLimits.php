<?php

/**
 * This class basically holds just the info about the time
 * limits a project can have with the proejct info meta
 * data keys: "project_max_hours_day" and the key
 * "project_max_hours_block".
 */

namespace Kanboard\Plugin\WeekHelper\Helper;


class ProjectsPlannedLimits
{
    /**
     * The internal array holding the info about the limits
     * of the consecutive worked tasks in a block on a day
     * per project with its project id as key and the info
     * as value, which should have this structure later on:
     *
     *     [
     *         'mon' => [
     *             slot_key => recent consecutive time for
     *                         tasks of this project.
     *         ],
     *         'tue' => ...
     *     ]
     *
     * So ultimately:
     *  [project_id => ['mon' => ...]]
     *
     * ATTENTION: If the user sets two slots after each other
     * with maybe just a few minutes (if at all) in between,
     * it could technically happen that more than the set
     * "project_max_hours_block" will be planned for a project.
     * At this stage the class is not aware of the slots
     * themself. I have no motivation (yet) to implement it.
     *
     * @var array
     **/
    var $max_block = [];

    /**
     * Similar like for block, but for days and without the
     * slot_key aspect.
     *
     * @var array
     **/
    var $max_day = [];

    /**
     * Add time to a block into a slot of a day for a project.
     * This is to keep track of the latest time for a project
     * and its consecutive tasks in a time slot on that day.
     * It can also be reset, e.g. in case another task of
     * another project will be planned. Then it's no more
     * consecutive tasks of the same project. So it's the
     * helping storing mechanics to suite the
     * "project_max_hours_block" project info.
     *
     * @param integer $project_id
     * @param string $day
     * @param integer $slot_key
     * @param integer $time
     */
    public function addTimeToBlock($project_id, $day, $slot_key, $time)
    {
        if (!array_key_exists($project_id, $this->max_block)) {
            $this->max_block[$project_id] = [
                'mon' => [],
                'tue' => [],
                'wed' => [],
                'thu' => [],
                'fri' => [],
                'sat' => [],
                'sun' => [],
            ];
        }
        if (!array_key_exists($slot_key, $this->max_block[$project_id][$day])) {
            $this->max_block[$project_id][$day][$slot_key] = 0;
        }
        $this->max_block[$project_id][$day][$slot_key] += $time;
    }

    /**
     * Reset a block, which can happen if a task of another project
     * will be planned, so that no consecutive tasks of the other
     * project is possible in this case.
     *
     * @param  integer $project_id
     * @param  string $day
     * @param  integer $slot_key
     */
    public function resetBlock($project_id, $day, $slot_key)
    {
        if (array_key_exists($project_id, $this->max_block)) {
            // since with the addTimeToBlock() method the main array
            // gets initialized with the project_id and the day keys,
            // I only have to check, if the "slot_key" key exists, since
            // this will be set by the user later on.
            if (array_key_exists($slot_key, $this->max_block[$project_id][$day])) {
                $this->max_block[$project_id][$day][$slot_key] = 0;
            }
        }
    }

    /**
     * Return the consecutive planned time for the project,
     * for the day and for the slot.
     *
     * @param  integer $project_id
     * @param  string $day
     * @param  integer $slot_key
     */
    public function getTimeForBlock($project_id, $day, $slot_key)
    {
        if (!array_key_exists($project_id, $this->max_block)) {
            // basically initialize some kind of empty block-time counter
            // here, if the given id did not exist.
            $this->addTimeToBlock($project_id, $day, $slot_key, 0);
        }
        return $this->max_block[$project_id][$day][$slot_key];
    }

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
        if (!array_key_exists($project_id, $this->max_day)) {
            $this->max_day[$project_id] = [
                'mon' => 0,
                'tue' => 0,
                'wed' => 0,
                'thu' => 0,
                'fri' => 0,
                'sat' => 0,
                'sun' => 0,
            ];
        }
        $this->max_day[$project_id][$day] += $time;
    }

    /**
     * Return the planned time for the whole day for the project.
     *
     * @param  integer $project_id
     * @param  string $day
     */
    public function getTimeForDay($project_id, $day)
    {
        if (!array_key_exists($project_id, $this->max_day)) {
            // basically initialize some kind of empty day-time counter
            // here, if the given id did not exist.
            $this->addTimeToDay($project_id, $day, 0);
        }
        return $this->max_day[$project_id][$day];
    }
}
