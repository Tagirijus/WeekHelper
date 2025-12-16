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
        foreach ($this->time_slot_days as &$time_slot_day) {
            // I iter through the tasks times the tasks itself, since
            // sometimes the same project / task may not be planned
            // consecutively, thus has to be put back and the next
            // task has to be checked. but the other task might
            // still have remaining time to be planned. so I should
            // re-check this this task again.
            // I am doing probably the most noobish thing here by
            // itering through the tasks and inside through the tasks
            // again, making it count(tasks) x the tasks.
            // I tried with a while loop, but this one got stuck in a loop.
            foreach ($tasks as $iter_task) {
                $remove_task_keys = [];
                foreach ($tasks as $key => &$task) {
                    if ($time_slot_day->planTask($task)) {
                        $remove_task_keys[] = $key;
                    }
                }
                // remove the tasks from the array so that the iteration
                // might be more efficient ... maybe it's premature
                // optimization ...
                foreach ($remove_task_keys as $key) {
                    unset($tasks[$key]);
                }
            }
        }
        return [
            'mon' => $this->time_slot_days['mon']->getTasks(),
            'tue' => $this->time_slot_days['tue']->getTasks(),
            'wed' => $this->time_slot_days['wed']->getTasks(),
            'thu' => $this->time_slot_days['thu']->getTasks(),
            'fri' => $this->time_slot_days['fri']->getTasks(),
            'sat' => $this->time_slot_days['sat']->getTasks(),
            'sun' => $this->time_slot_days['sun']->getTasks(),
            'overflow' => $this->time_slot_days['overflow']->getTasks(),
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
