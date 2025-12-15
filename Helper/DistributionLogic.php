<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


class DistributionLogic
{
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
     * @param  array $tasks
     * @param  array $time_slots
     * @return array
     */
    public function distributeTasks($tasks)
    {
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
}
