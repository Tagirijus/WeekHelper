<?php

/**
 * This clas holds the final plan for the tasks. It holds all the
 * tasks in an array with all the needed information. It can hold
 * tasks twice, though, since maybe sometimes tasks got planned
 * on multiple days, since a task did not fit fully into another
 * day and / or block.
 */

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
use Kanboard\Plugin\WeekHelper\Model\ProjectQuota;
use Kanboard\Plugin\WeekHelper\Model\TimesCalculator;


class TasksPlan
{
    /**
     * An array, holding basically the info about the
     * TimeSlotsDay minutes, as available times per day,
     * coming from the DistributionLogic.
     *
     * @var array
     **/
    var $available_day_times = [
        'mon' => 1440,
        'tue' => 1440,
        'wed' => 1440,
        'thu' => 1440,
        'fri' => 1440,
        'sat' => 1440,
        'sun' => 1440,
    ];

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
     * The minutes a Kanboard task score should be. So
     * far this value is used for tasks, which are
     * open, but have overtime. My logic here is just
     * to use the non_time_mode_minutes as the remaining
     * time for such tasks. They might take longer or
     * shorter, but I need anything so that such still-open
     * tasks can be planned.
     *
     * @var integer
     **/
    var $non_time_mode_minutes;

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
     * This attribute will hold info about which task
     * is open and has overtime, but still was used
     * in the plan already. I need this to have "still
     * open tasks" shown in the week plan, even if
     * they technically normally do not have remaining
     * time left. They may appear once in the plan. Then
     * their ID gets added to this array so that they
     * do not appear once more in the week plan.
     *
     * @var array
     **/
    var $open_overtime_task_ids = [];

    /**
     * Project quotas by project id:
     *
     *  [
     *      project_id => ProjectQuota
     *  ]
     *
     * @var array
     **/
    var $project_quotas = [];

    /**
     * The optional TaskTimesPreparer, which is needed for
     * depleting the ProjectQuota by the spent times.
     *
     * @var null|TaskTimesPreparer
     **/
    var $task_times_preparer = null;

    /**
     * Initialize the instance.
     *
     * @param integer $min_slot_length
     * @param integer $non_time_mode_minutes
     * @param null|array $available_day_times
     * @param null|TaskTimesPreparer $task_times_preparer
     */
    public function __construct(
        $min_slot_length = 0,
        $non_time_mode_minutes = 0,
        $available_day_times = null,
        $task_times_preparer = null
    )
    {
        $this->min_slot_length = $min_slot_length;
        $this->non_time_mode_minutes = $non_time_mode_minutes != 0 ? $non_time_mode_minutes : 5;
        if (!is_null($available_day_times)) {
            $this->available_day_times = $available_day_times;
        }
        if (!is_null($task_times_preparer)) {
            $this->task_times_preparer = $task_times_preparer;
        }
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
        return max(0, $remain - $planned);
    }

    /**
     * Get ProjectQuota by project id of the given task.
     * I do want the whole task here, since: if there is
     * no ProjectQuota yet, it should be initialized
     * with the values from the whole task. That's why I
     * would need its array values as well and not just
     * the project id.
     *
     * @param  array $task
     * @return ProjectQuota
     */
    public function getProjectQuotaByTask($task)
    {
        if (!array_key_exists($task['project_id'], $this->project_quotas)) {
            $this->initProjectQuota($task);
        }
        return $this->project_quotas[$task['project_id']];
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

        // otherwise get the daily remaining quota by the internal ProjectQuota
        // intsance belonging to the task
        return $this->getProjectQuotaByTask($task)->getQuota($day);
    }

    /**
     * Initialize the project quota with the given task, while also
     * considering the daily available times, coming from the internal
     * available_day_times attribute, which was set by DistributionLogic's
     * TimeSlotsDay instances. Means: a project might have daily limits. But
     * if the limit is higher than the actual available day time defined by
     * the slots, the slots should override it.
     *
     * @param  array $task
     */
    protected function initProjectQuota($task)
    {
        $this->project_quotas[$task['project_id']] = new ProjectQuota($task);

        // adjust the ProjectQuota to the daily available times according
        // to the TimeSlotsDays times
        foreach ($this->available_day_times as $day => $minutes) {
            if ($this->project_quotas[$task['project_id']]->getQuota($day) > $minutes) {
                $this->project_quotas[$task['project_id']]->setQuota($day, $minutes);
            }
        }

        // "deplete" the ProjectQuota by already spent times, if TaskTimesPreparer
        // was given in the constructor
        if (!is_null($this->task_times_preparer)) {
            // TODO: Logic to deplete ProjectQuote here!!
        }
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
            $task,
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
                $task,
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

            // update the project quota
            $this->getProjectQuotaByTask($task)->substractQuota(
                $time_slots_day->getDay(), $time_to_plan
            );

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
        // the task is still open and has overtime already
        if (
            !TimesCalculator::isDone($task)
            && TimesCalculator::calculateOvertime(
                $task['time_estimated'],
                $task['time_spent'],
                TimesCalculator::isDone($task)
            )
            && !in_array($task['id'], $this->open_overtime_task_ids)
        ) {
            // in that case just use the non_time_mode_minutes
            // as the planable minutes, since it is quite of
            // unclear anyway, when the task will be done
            $this->open_overtime_task_ids[] = $task['id'];
            return $this->non_time_mode_minutes;
        }

        // this task is completely planned already
        $tasks_actual_remaining = $this->getTasksActualRemaining($task);
        if ($tasks_actual_remaining == 0) {
            return 0;
        }

        // check project day limit
        $left_daily_limit = $this->getLeftDailyTime($task, $time_slots_day->getDay());
        if ($left_daily_limit <= 0) {
            return 0;
        }

        // get next possible slot
        $time_point_str = $task['plan_from'] ?? '';
        $next_slot_key = $time_slots_day->nextSlot($task, $time_point_str);
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
