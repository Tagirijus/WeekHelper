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
     *             [
     *                 'task' => kanboard task array,
     *                 'start' => starting time,
     *                 'end' => ending time,
     *                 'length' => length in minutes,
     *                 'spent' => spent in minutes,
     *                 'remaining' => remaining in minutes
     *             ], ...
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
     * Per project storage of how much for which day a
     * project was planned already. Structure:
     *     [
     *         project_id => [
     *             'mon' => time,
     *             'tue' => time,
     *             ...
     *         ]
     *     ]
     *
     * @var array
     **/
    var $planned_project_times = [];

    /**
     * The minimum amount of minutes a slot should have
     * available for a task to be planned on. Otherwise
     * the slot will get depleted automatically.
     *
     * @var integer
     **/
    var $min_slot_length = 0;

    /**
     * This variable holds the overall planned, spent and
     * remaining times per week and per day. Structure:
     *
     *      [
     *          'task_ids' => [0, 1, 2, 3, ...],
     *          'week' => [
     *              'remaining' => 0,
     *              'spent' => 0,
     *              'planned' => 0
     *          ],
     *          'mon' => [
     *              'remaining' => 0,
     *              'spent' => 0,
     *              'planned' => 0
     *          ],
     *          'tue' => ...
     *      ]
     * The "task_ids" key is for storing info about which
     * tasks where added to the internal "remaining" and
     * "spent" times already. Tasks should only add once
     * to these values, after all.
     *
     * @var array
     **/
    var $global_times = [
        'task_ids' => [],
        'week' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'mon' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'tue' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'wed' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'thu' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'fri' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'sat' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'sun' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
        'overflow' => ['remaining' => 0, 'spent' => 0, 'planned' => 0],
    ];

    /**
     * If set to true, the instance will use the tasks
     * "time_spent" instead of the "time_remaining" key.
     * This is used for "planning" a week, or better,
     * basically getting what work was done already, probably.
     * This way I get a planned_project_times array I cen then
     * pass onto the actual week planning TasksPlan instance,
     * which will be able to understand for which projects
     * tasks were processed already and use the updated
     * project limits.
     *
     * For example: in a week I might have worked for a project
     * for 5 hours already and it is Wednesday. Maybe the
     * project daily limit is 2h. This would mean that on Wednesday
     * only 1 hour is left to plan for this project.
     *
     * @var boolean
     **/
    var $worked_mode = false;

    /**
     * Initialize the instance.
     *
     * @param integer $min_slot_length
     * @param boolean $worked_mode
     */
    public function __construct($min_slot_length = 0, $worked_mode = false)
    {
        $this->min_slot_length = $min_slot_length;
        $this->worked_mode = $worked_mode;
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
        if ($this->worked_mode) {
            // see doc comment at start of class to understand what
            // this is basically for!
            $remain = TimeHelper::hoursToMinutes($task['time_spent']);
        } else {
            $remain = TimeHelper::hoursToMinutes($task['time_remaining']);
        }
        $planned = $this->getPlannedTimeForTask($task['id']);
        return $remain - $planned;
    }

    /**
     * Add planned time to the project.
     *
     * @param integer $project_id
     * @param string $day
     * @param integer $time
     */
    public function addPlannedTimeForProject($project_id, $day, $time)
    {
        if (!array_key_exists($project_id, $this->planned_project_times)) {
            $this->planned_project_times[$project_id] = [
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
        $this->planned_project_times[$project_id][$day] += $time;
    }

    /**
     * Return the planned time for the whole day for the project.
     *
     * @param  integer $project_id
     * @param  string $day
     */
    public function getPlannedTimeForProject($project_id, $day)
    {
        if (!array_key_exists($project_id, $this->planned_project_times)) {
            // basically initialize some kind of empty day-time counter
            // here, if the given id did not exist.
            $this->addPlannedTimeForProject($project_id, $day, 0);
        }
        return $this->planned_project_times[$project_id][$day];
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
        // "overflow", for example, should not have a project daily limit.
        // it should be able to ignore it, since this special "day" is supposed
        // to hold all overflowed tasks I could not plan into the week
        if (!in_array($day, ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])) {
            // basically return minutes of a whole single day, which should be
            // a phyiscal max value
            return 1440;
        }

        // otherwise go on as usual for the weekdays
        // also, rather new feature: it is possible to assign individual
        // project daily limits per weekday. keys like "project_max_hours_mon"
        // can exist with a daily hours number (this will be used) or "-1" as
        // a value (project_max_hours_day will be used instead then)
        if ($task['project_max_hours_' . $day] != -1) {
            $project_daily_limit = TimeHelper::hoursToMinutes($task['project_max_hours_' . $day]);
        } else {
            $project_daily_limit = TimeHelper::hoursToMinutes($task['project_max_hours_day']);
        }
        $project_id = $task['project_id'];
        return $project_daily_limit - $this->getPlannedTimeForProject($project_id, $day);
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

        // a task can have a "plan_from" timepoint string.
        // this should be able to split an available time slot into
        // two slots. This way it is easier to assign a task into the
        // slot and deplete it, etc. So basically before doing the
        // main loop for planning the task into any slot, the task
        // is capable to split the slots first.
        $time_point_str = $task['plan_from'] ?? '';
        $time_slots_day->splitSlotByTimepointString($time_point_str);
        // also directly return false, if for the given task
        // with (maybe) a plan_from there is no next
        // slot at all.
        $next_slot_key = $time_slots_day->nextSlot(
            $task['project_type'],
            $time_point_str
        );
        if ($next_slot_key == -1) {
            return $success;
        }

        // try to plan the given task over all available time slots
        // of the TimeSlotsDay instance. e.g. it can happen that
        // there might be more than one slot for this task to be used,
        // in case a single slot might not have enough time for the task,
        // but other remaining slots would have.
        foreach ($time_slots_day->getSlots() as $slot) {

            // first it has to be checked, if there even is enough time
            // for the task to be planned available
            $time_to_plan = $this->minutesCanBePlanned($task, $time_slots_day);

            // also this tasks times has to be added already, even if no time
            // for it is to plan. I want the remaining and especially the spent
            // time anyway added into the global array!
            $this->addTimesToGlobal($time_slots_day->getDay(), $time_to_plan, $task);
            // and now check; no time to plan? maybe next slot then?
            if ($time_to_plan == 0) {
                continue;
            }

            // then there should be an available slot left on the day
            $next_slot_key = $time_slots_day->nextSlot(
                $task['project_type'],
                $time_point_str
            );
            if ($next_slot_key == -1) {
                break;
            }

            // finally get some variables for the planning and plan it
            $start = $time_slots_day->getStartOfSlot($next_slot_key);
            $end = $start + $time_to_plan;
            $plan_success = $time_slots_day->planTime($next_slot_key, $time_to_plan, $start);
            $success = $plan_success == '';

            // update the limits in ProjectConditions
            $this->addPlannedTimeForProject($task['project_id'], $time_slots_day->getDay(), $time_to_plan);

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
            'spent' => TimeHelper::hoursToMinutes($task['time_spent']),
            'remaining' => TimeHelper::hoursToMinutes($task['time_remaining'])
        ];
    }

    /**
     * Add times to the internal global array, which holds information
     * about on which day or for the whole week how the times are; like
     * remainin, spent and planned times.
     *
     * @param string $day
     * @param integer $minutes_planned
     * @param array $task
     */
    public function addTimesToGlobal($day, $minutes_planned, $task)
    {
        $task_id = $task['id'];
        if (!in_array($task_id, $this->global_times['task_ids'])) {
            $remaining = TimeHelper::hoursToMinutes($task['time_remaining']);
            $spent = TimeHelper::hoursToMinutes($task['time_spent']);
            $this->global_times['task_ids'][] = $task_id;
        } else {
            $remaining = 0;
            $spent = 0;
        }
        $this->global_times['week']['remaining'] += $remaining;
        $this->global_times['week']['spent'] += $spent;
        // only add planned time for the real week days;
        // not for the overflow. the later one is stored
        // in the "global_times['overflow']" array anyway!
        if (in_array($day, ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])) {
            $this->global_times['week']['planned'] += $minutes_planned;
        }
        $this->global_times[$day]['remaining'] += $remaining;
        $this->global_times[$day]['spent'] += $spent;
        $this->global_times[$day]['planned'] += $minutes_planned;
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
        $left_daily_limit = $this->getLeftDailyTime($task, $time_slots_day->getDay());
        if ($left_daily_limit == 0) {
            return 0;
        }

        // get next possible slot
        $time_point_str = $task['plan_from'] ?? '';
        $next_slot_key = $time_slots_day->nextSlot($task['project_type'], $time_point_str);
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

        // does the slot have enough time according to the config
        // minimum slot length?
        if ($slot_length < $this->min_slot_length) {
            $time_slots_day->depleteSlot($next_slot_key);
            return 0;
        }

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
     *
     * @param  array $plan
     * @return array
     */
    public static function sortPlan($plan)
    {
        foreach ($plan as &$tasks) {
            usort($tasks, function ($a, $b) {
                if ($a['start'] == $b['start']) {
                    return 0;
                }
                return ($a['start'] < $b['start']) ? -1 : 1;
            });
        }
        return $plan;
    }

    /**
     * Return the final plan, which basically is just the internal
     * array.
     *
     * @return array
     */
    public function getPlan()
    {
        $this->plan = self::sortPlan($this->plan);
        return $this->plan;
    }

    /**
     * Set the internal minimum slot length.
     *
     * @param integer $min_slot_length
     */
    public function setMinSlotLength($min_slot_length = 0)
    {
        $this->min_slot_length = $min_slot_length;
    }

    /**
     * Copy the internal planned_project_times from another given
     * TasksPlan instance to this internal attribut accordingly.
     *
     * @param  TasksPlan $tasks_plan
     */
    public function copyPlannedProjectTimesFromTasksPlan($tasks_plan)
    {
        $this->planned_project_times = $tasks_plan->planned_project_times;
    }

    /**
     * Return the internal array part for the week times.
     *
     * @return array
     */
    public function getGlobalTimesForWeek()
    {
        return $this->global_times['week'];
    }

    /**
     * Return the internal array part for the times for
     * the specified weekday.
     *
     * @param  string $day
     * @return array
     */
    public function getGlobalTimesForDay($day = 'mon')
    {
        return $this->global_times[$day];
    }

    /**
     * Basically a wrapper for getting the "overflow" key
     * value from getGlobalTimesForDay().
     *
     * @return array
     */
    public function getGlobalTimesForOverflow()
    {
        return $this->global_times['overflow'];
    }

    /**
     * Combine two given plans and sort them accordingly.
     * This can combine two TasksPlan task arrays. It also
     * can (that's why I am coding it) combine a TasksPlan
     * task array with a blocking_pseudo_tasks array from
     * the DistributionLogic, which is needed for the
     * feature to also print out the blocking tasks.
     *
     * @param  array $plan_a
     * @param  array $plan_b
     * @return array
     */
    public static function combinePlans($plan_a, $plan_b)
    {
        $out = [
            'mon' => array_merge($plan_a['mon'], $plan_b['mon']),
            'tue' => array_merge($plan_a['tue'], $plan_b['tue']),
            'wed' => array_merge($plan_a['wed'], $plan_b['wed']),
            'thu' => array_merge($plan_a['thu'], $plan_b['thu']),
            'fri' => array_merge($plan_a['fri'], $plan_b['fri']),
            'sat' => array_merge($plan_a['sat'], $plan_b['sat']),
            'sun' => array_merge($plan_a['sun'], $plan_b['sun']),
            'overflow' => array_merge($plan_a['overflow'], $plan_b['overflow']),
        ];
        return self::sortPlan($out);
    }
}
