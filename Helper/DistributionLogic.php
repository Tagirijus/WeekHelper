<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TimeSlotDay;


class DistributionLogic
{
    /**
     * An array with all the TimeSlotDay instances, which
     * are capable of distributing task on "their own day".
     * The last ("overflow") instance is basically a day
     * with many hours of limit. So tasks, which do not
     * fit into the week, will fit on this "special day".
     *
     * @var array
     **/
    var $time_slot_days = [
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
     * @param  array $time_slots_config
     * @return array
     */
    public function distributeTasks($tasks, $time_slots_config)
    {
        $this->parseTimeSlots($time_slots_config);
        foreach ($tasks as $key => &$task) {
            foreach ($this->time_slot_days as $day => &$time_slot_day) {
                // for this (or none type) no more time left to assign on this day
                if (!$time_slot_day->timeLeft($task['project_type'])) {
                    continue;

                // plan the task onto the day, as much as you can
                } else {
                    if ($time_slot_day->planTask($task)) {
                        // basically means "go to the next task"
                        break;
                    }
                }
            }
        }
        return [
            'mon' => [],
            'tue' => [],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => []
        ];
    }

    /**
     * Parse the raw strings from the config for the time slots
     * and create usable data from it (maybe objects or so).
     *
     * @param  array $time_slots_config
     */
    public function parseTimeSlots($time_slots_config)
    {
        $this->time_slot_days['mon'] = new TimeSlotDay($time_slots_config['mon']);
        $this->time_slot_days['tue'] = new TimeSlotDay($time_slots_config['tue']);
        $this->time_slot_days['wed'] = new TimeSlotDay($time_slots_config['wed']);
        $this->time_slot_days['thu'] = new TimeSlotDay($time_slots_config['thu']);
        $this->time_slot_days['fri'] = new TimeSlotDay($time_slots_config['fri']);
        $this->time_slot_days['sat'] = new TimeSlotDay($time_slots_config['sat']);
        $this->time_slot_days['sun'] = new TimeSlotDay($time_slots_config['sun']);
        $this->time_slot_days['overflow'] = new TimeSlotDay('0:00-100:00');
    }
}
