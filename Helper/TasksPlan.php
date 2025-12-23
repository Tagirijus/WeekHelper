<?php

/**
 * This clas holds the final plan for the tasks. It holds all the
 * tasks in an array with all the needed information. It can hold
 * tasks twice, though, since maybe sometimes tasks got planned
 * on multiple days, since a task did not fit fully into another
 * day and / or block.
 */

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


class TasksPlan
{
    /**
     * The final big array, holding the plan. The structure
     * of this array is this:
     *     [
     *         'mon' => [
     *             ['task' => kanboard task array,
     *              'start' => starting time, 'end' => ...]
     *         ],
     *         'tue' => ...
     *     ]
     *
     * @var array
     **/
    var $plan = [
        'mon' => [],
        'tue' => [],
        'wed' => [],
        'thu' => [],
        'fri' => [],
        'sat' => [],
        'sun' => [],
        'overflow' => [],
    ];

    /**
     * This array holds the tasks with their ID as the key
     * and the already planned time for the task. Structure:
     *     [
     *         task_id => planned time in minutes,
     *         ...
     *     ]
     *
     * @var array
     **/
    var $planned_tasks_times = [];

    /**
     * The ProjectConditions instance from outside
     * here as a reference.
     *
     * @var ProjectConditions
     **/
    var $project_conditions = null;

    /**
     * The minimum amount of minutes a slot should have
     * available for a task to be planned on. Otherwise
     * the slot will get depleted automatically.
     *
     * @var integer
     **/
    var $min_slot_length = 0;

    /**
     * Initialize the instance with the given ProjectConditions
     * instance, which will be used and updated later on.
     *
     * @param integer $min_slot_length
     */
    public function __construct($min_slot_length = 0)
    {
        $this->project_conditions = new ProjectConditions();
        $this->min_slot_length = $min_slot_length;
    }

    /**
     * Add planned time for a task to the internal attribute,
     * which is keeping track of it.
     *
     * @param integer $task_id
     * @param integer $time
     */
    public function addPlannedTimeForTask($task_id, $time)
    {
        if (!array_key_exists($task_id, $this->planned_tasks_times)) {
            $this->planned_tasks_times[$task_id] = $time;
        } else {
            $this->planned_tasks_times[$task_id] += $time;
        }
    }

    /**
     * Return the already planned time in minutes for the
     * task with the given id.
     *
     * @param  integer $task_id
     * @return integer
     */
    public function getPlannedTimeForTask($task_id)
    {
        if (array_key_exists($task_id, $this->planned_tasks_times)) {
            return $this->planned_tasks_times[$task_id];
        } else {
            return 0;
        }
    }

    /**
     * Return the "real" remaining time for a task after it's current
     * planning. Basically this will get the original tasks remaining
     * time and subtract this from the already planned time.
     *
     * @param  array $task
     * @return integer
     */
    public function getTasksActualRemaining($task)
    {
        $remain = TimeHelper::hoursToMinutes($task['time_remaining']);
        $planned = $this->getPlannedTimeForTask($task['id']);
        return $remain - $planned;
    }

    /**
     * Plan the given task into the time slot, which will automatically get
     * the correct next time slot key. Only the length is needed from
     * outside this method.
     *
     * Returns "true", if the task was planned, otherwise "false". It has
     * an internal conditions check for that.
     *
     * @param  array $task
     * @param  TimeSlotsDay &$time_slots_day
     * @return boolean
     */
    public function planTask($task, &$time_slots_day)
    {
        $success = false;

        // try to plan the given task over all available time slots
        // of the TimeSlotsDay instance. e.g. it can happen that
        // there might be more than one slot for this task to be used,
        // in case a single slot might not have enough time for the task,
        // but other remaining slots would have.
        foreach ($time_slots_day->getSlots() as $slot) {

            // first it has to be checked, if there even is enough time
            // for the task to be planned available
            $time_to_plan = $this->minutesCanBePlanned($task, $time_slots_day);
            if ($time_to_plan == 0) {
                break;
            }

            // then there should be an available slot left on the day
            $next_slot_key = $time_slots_day->nextSlot($task['project_type']);
            if ($next_slot_key == -1) {
                break;
            }

            // finally get some variables for the planning and plan it
            $start = $time_slots_day->getStartOfSlot($next_slot_key);
            $end = $start + $time_to_plan;
            $plan_success = $time_slots_day->planTime($next_slot_key, $time_to_plan, $start);
            $success = $plan_success == '';

            // update the limits in ProjectConditions
            $this->project_conditions->addTimeToDay($task['project_id'], $time_slots_day->getDay(), $time_to_plan);

            // add task to the internal planning array and update other
            // needed internal attributes
            $this->addPlannedTimeForTask($task['id'], $time_to_plan);
            $this->addTaskToPlan($task, $time_slots_day->getDay(), $start, $end);
        }

        return $success;
    }

    /**
     * Add a task into the internal plan finally.
     *
     * @param array $task
     * @param string $day
     * @param integer $start
     * @param integer $end
     */
    public function addTaskToPlan($task, $day, $start, $end)
    {
        $this->plan[$day][] = [
            'task' => $task,
            'start' => $start,
            'end' => $end,
            'length' => $end - $start,
        ];
    }

    /**
     * BASICALLY THIS IS THE CONDITION CHECKER FOR TASK.
     *
     * Check the conditions on which a task can be planned.
     * Outputs the possible length of the task, which could
     * be planned. So basically "0" means that a condition
     * wasn't fulfilled.
     *
     * @param  array $task
     * @param  TimeSlotsDay $time_slots_day
     * @return integer
     */
    public function minutesCanBePlanned($task, $time_slots_day)
    {
        // this task is completely planned already
        $tasks_actual_remaining = $this->getTasksActualRemaining($task);
        if ($tasks_actual_remaining == 0) {
            return 0;
        }

        // check project day limit
        $left_daily_limit = $this->project_conditions->getLeftDailyTime($task, $time_slots_day->getDay());
        if ($left_daily_limit == 0) {
            return 0;
        }

        // get next possible slot
        $next_slot_key = $time_slots_day->nextSlot($task['project_type']);
        if ($next_slot_key == -1) {
            return 0;
        }

        // start time
        $start = $time_slots_day->getStartOfSlot($next_slot_key);
        if ($start == -1) {
            // should not happen normally ...
            return 0;
        }

        // available length of slot
        $slot_length = $time_slots_day->getLengthOfSlot($next_slot_key);

        // get possible time to plan and return it
        // prios here are:
        // - complete actual remaining of task, if project max and slot length allows it
        // - otherwise only project max, if slot allows it
        // - otherwisw only the available slot length
        $out = (
            ($tasks_actual_remaining > $left_daily_limit)
            ? $left_daily_limit : $tasks_actual_remaining
        );
        $out = (
            ($out > $slot_length)
            ? $slot_length : $out
        );
        return $out;
    }

    /**
     * Sort the tasks on the days according to their starting time.
     */
    public function sortPlan()
    {
        foreach ($this->plan as &$tasks) {
            usort($tasks, function ($a, $b) {
                if ($a['start'] == $b['start']) {
                    return 0;
                }
                return ($a['start'] < $b['start']) ? -1 : 1;
            });
        }
    }

    /**
     * Return the final plan, which basically is just the internal
     * array.
     *
     * @return array
     */
    public function getPlan()
    {
        $this->sortPlan();
        return $this->plan;
    }
}
