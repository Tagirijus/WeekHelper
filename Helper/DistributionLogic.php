<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TasksPlan;
use Kanboard\Plugin\WeekHelper\Helper\TimeSlotsDay;


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
        $this->tasks_plan = new TasksPlan();
    }

    /**
     * Distirbute the tasks among the defined time slots.
     * The time slots array is basically all the seven
     * week days and the raw string form the config.
     * It will be parsed by another class. Initially it
     * still should be in the structure:
     *     [
     *         'mon' => raw config string,
     *         'tue' => raw config string,
     *         ...
     *         'sun' => raw config string,
     *     ]
     *
     * Finally return a distributed array in the
     * structure:
     *     [
     *         'mon' => [array with sorted tasks],
     *         'tue' => [array with sorted tasks],
     *         ...
     *         'sun' => [array with sorted tasks],
     *         'overflow' => [array with sorted tasks]
     *     ]
     * While "overflow" will hold the tasks, which did not
     * fit anymore into the time slots.
     *
     * The time_slots_config hold the raw config data for
     * every weekday:
     *     [
     *         'mon' => raw_config_string,
     *         'tue' => raw_config_string,
     *         ...
     *     ]
     *
     * @param  array $tasks
     */
    public function distributeTasks($tasks)
    {
        foreach ($this->time_slots_days as $time_slots_day) {
            // TODO:
            // Deplete all slots before "NOW" and continue with next possible slot.
            // So:
            // - $day is before "today"? -> deplete all slots of this day, continued
            // - $day is exactly "today"? -> deplete slots until "NOW" is reached
            // - $day is after "now"? -> go on as usual
            //
            // I could need a method, whit which TimeSlotsDay can be depleted by
            // TimeSpans. So I could have a time span and this would be planned
            // or deplete the slots accordingly. This way I could on the one hand
            // deplete whole days and time spans "till now". But on the other hand
            // I could use this methods as well to "deplete" certain time spans
            // in the week, which could stand for dates I already set in my
            // calendar or so.
            //
            // CODE HERE LATER

            // I iter through the tasks times the tasks itself, since
            // sometimes the same project / task may not be planned
            // consecutively, thus has to be put back and the next
            // task has to be checked. but the other task might
            // still have remaining time to be planned. so I should
            // re-check this this task again.
            // I am doing probably the most noobish thing here by
            // itering through the tasks and inside through the tasks
            // again, making it count(tasks) x the tasks.
            // I tried with a while loop, but this one got stuck in an
            // endless loop, unfortunately.
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
}
