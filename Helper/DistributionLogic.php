<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TasksPlan;
use Kanboard\Plugin\WeekHelper\Helper\TimeSlotsDay;
use Kanboard\Plugin\WeekHelper\Helper\TimePoint;


class DistributionLogic
{
    /**
     * The global tasks plan instance.
     *
     * @var TasksPlan
     **/
    var $tasks_plan;

    /**
     * An array with all the TimeSlotsDay instances, which
     * are capable of holding slot capacities.
     * The last ("overflow") instance is basically a day
     * with many hours of limit. So tasks, which do not
     * fit into the week, will fit on this "special day".
     *
     * @var array
     **/
    var $time_slots_days = [
        'mon' => null,
        'tue' => null,
        'wed' => null,
        'thu' => null,
        'fri' => null,
        'sat' => null,
        'sun' => null,
        'overflow' => null,
    ];

    /**
     * Initialize the class with its attributes.
     *
     * @param string  $time_slots_config
     */
    public function __construct($time_slots_config = '')
    {
        $this->parseTimeSlots($time_slots_config);
        $this->tasks_plan = new TasksPlan($time_slots_config['min_slot_length']);
    }

    /**
     * Distirbute the tasks among the defined internal time slots.
     * This method simply iters through the internal TimeSlotsDay
     * instances and use them to plan tasks with the TasksPlan
     * method, which will handle the final logic.
     *
     * @param  array $tasks
     */
    public function distributeTasks($tasks)
    {
        foreach ($this->time_slots_days as $time_slots_day) {
            foreach ($tasks as $task) {
                $this->tasks_plan->planTask($task, $time_slots_day);
            }
        }
    }

    /**
     * Parse the raw strings from the config for the time slots
     * and create usable data from it (maybe objects or so).
     *
     * @param  array $time_slots_config
     */
    public function parseTimeSlots($time_slots_config)
    {
        $this->time_slots_days['mon'] = new TimeSlotsDay($time_slots_config['mon'], 'mon');
        $this->time_slots_days['tue'] = new TimeSlotsDay($time_slots_config['tue'], 'tue');
        $this->time_slots_days['wed'] = new TimeSlotsDay($time_slots_config['wed'], 'wed');
        $this->time_slots_days['thu'] = new TimeSlotsDay($time_slots_config['thu'], 'thu');
        $this->time_slots_days['fri'] = new TimeSlotsDay($time_slots_config['fri'], 'fri');
        $this->time_slots_days['sat'] = new TimeSlotsDay($time_slots_config['sat'], 'sat');
        $this->time_slots_days['sun'] = new TimeSlotsDay($time_slots_config['sun'], 'sun');
        $this->time_slots_days['overflow'] = new TimeSlotsDay('0:00-100:00', 'overflow');
    }

    /**
     * Return the spicey task plan finally!
     *
     * @return array
     */
    public function getTasksPlan()
    {
        return $this->tasks_plan->getPlan();
    }

    /**
     * Deplete all time slots until the given TimePoint. This
     * basically could, for example, deplete all the time slots
     * on all days before "now". But for testing purposes this
     * can also be any other TimePoint.
     *
     * @param  TimePoint $time_point
     */
    public function depleteUntilTimePoint($time_point)
    {
        // iter through all time_slots_days values,
        // until the given TimePoint difference in days is positive
        foreach ($this->time_slots_days as &$time_slots_day) {
            $day_diff = $time_slots_day->dayDiffFromTimePoint($time_point);

            // basically stop completely, if the given time point is
            // any day after the TimeSlotsDay day itself.
            if ($day_diff < 0) {
                return;

            // deplete whole days, if it is before the day
            } elseif ($day_diff > 0) {
                $time_slots_day->deplete();

            // same day now; deplete the given day TO the given
            // TimePoint start
            } elseif ($day_diff == 0) {
                $time_slots_day->depleteByTimePoint($time_point);
            }
        }
    }

    /**
     * Will create a TimePoint automatically internally for "now"
     * and deplete the week until this point.
     */
    public function depleteUntilNow()
    {
        $this->depleteUntilTimePoint(new TimePoint());
    }
}
