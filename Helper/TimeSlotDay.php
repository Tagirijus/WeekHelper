<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


class TimeSlotDay
{
    /**
     * All slots for this day. This contains the set times,
     * the still-available times and their type, of course.
     *
     * A slot might have the following structure:
     *     [
     *         'start' => int in minutes, representing the
     *                    daytime start. like 6 o'clock
     *                    being 360,
     *         'end' => int like for start, but for the end
     *                  of this slot,
     *         'remain' => remaining time in min for this slot,
     *         'next' => int for the next to plan time; like
     *                   if tasks where planned already, the
     *                   remaining time shrinks, but also the
     *                   starting point might increase. I do not
     *                   want the original 'start' value to be altered,
     *                   though, so I have this temporary value as well,
     *         'type' => type of this slot or empty string for ALL
     *     ]
     *
     * @var array
     **/
    var $slots = [];

    /**
     * All the planned tasks for this day in the hopefully correct order.
     * Also there will be start and end (and other) values added to the
     * task array.
     *
     * @var array
     **/
    var $tasks = [];

    /**
     * An array, keeping track of the planned time per project,
     * basically. The key is the project_id and the values are
     * the planned time for the project. This one is to keep
     * track of the "project_max_hours_day" project info.
     *
     * @var array
     **/
    var $planned_time_per_project_ids = [];

    /**
     * An array holding the last planned time in total
     * for a consecutive block of the same project.
     * This one is for the check of the
     * "project_max_hours_block" project info.
     *
     * @var array
     **/
    var $planned_time_block = [
        'project_id' => -1,
        'time' => 0
    ];

    /**
     * Initialize a time slot day instance with the given
     * raw config string for this day.
     *
     * @param string $config_string
     */
    public function __construct($config_string)
    {
        $this->initSlots($config_string);
    }

    /**
     * Initialize the internal slots variable and more variables.
     * The config string probably are the ones for the days fomr the
     * config, containing each line a time slot and an optional type.
     *
     * ATTENTION: I might not code some kind of logic-correcter here,
     * which would correct unlogical entered time slots. E.g. you will
     * be able to have a time slot "6:00-9:00" but at the same time
     * "4:00-10:00", which would not make sense due to two slots
     * overlapping. I am lazy ... the user just has to enter correct
     * time slots. This class here will use each kind of time slot
     * separately. And who knows; maybe this can even become handy
     * at some point. Like having surreal days or maybe timeslots for
     * different kind of things, which can go in parallel ...
     *
     * @param  string $config_string
     */
    public function initSlots($config_string)
    {
        $this->slots = [];
        $lines = explode("\r\n", $config_string ?? '');
        sort($lines);
        foreach ($lines as $line) {
            // splitting initial config string into times and type
            $parts = preg_split('/\s+/', $line);
            $times = $parts[0];
            if (count($parts) > 1) {
                $type = $parts[1];
            } else {
                $type = '';
            }

            // now splitting times into start and end
            $times_parts = preg_split('/\-/', $times);
            if (count($times_parts) == 2) {
                $start = $this->parseTimeIntoMinutes($times_parts[0]);
                $end = $this->parseTimeIntoMinutes($times_parts[1]);
            } else {
                $start = 0;
                $end = 0;
            }

            // add a time slot finally
            $this->slots[] = [
                'start' => $start,
                'end' => $end,
                'remain' => $end - $start,
                'next' => $start,
                'type' => $type
            ];
        }
    }

    /**
     * Parse a time string like "6:35" into int minutes "95".
     *
     * @param  string $time_string
     * @return int
     */
    public function parseTimeIntoMinutes($time_string)
    {
        list($hours, $minutes) = array_map('intval', explode(':', $time_string, 2));
        return ($hours * 60) + $minutes;
    }

    /**
     * Find the next slot. This function retuns -1, if no
     * slot was found (maybe, because no time left for the day).
     * Otherwise it will return the key of the internal slot array
     * for the slot, which still has remaining time to plan left.
     *
     * @param  string $type
     * @return int
     */
    public function nextSlot($type = '')
    {
        foreach ($this->slots as $key => $slot) {
            // if a slot type is empty, it means that every project_type
            // may be planned here!
            $type_is_valid = $slot['type'] == $type || $slot['type'] == '';
            $has_remaining_time = $slot['remain'] > 0;
            if ($type_is_valid && $has_remaining_time) {
                return $key;
            }
        }
        return -1;
    }

    /**
     * Plan the given task to this day as much as you can.
     * Returns a bool, while "true" means that the whole
     * task could be planned to this day and "false" means
     * that there is still time left to be planned for this
     * task.
     *
     * Also this method might create the temporary time-slot-
     * day keys onto the tasks array for further processing.
     *
     * @param  array &$task
     * @return bool  "true" == task fully planned, "false" == still time to plan
     */
    public function planTask(&$task)
    {
        $this->initAdditionalTaskValues($task);

        $slot_key = $this->nextSlot($task['project_type']);
        if ($slot_key != -1) {
            $slot = $this->slots[$slot_key];
            $start = $slot['next'];
            $end = $this->calculateEndOfSlot($task, $slot);

            $project_remaining_time_for_day = $this->projectMaxHoursDayRemain($task);
            $project_remaining_time_for_block = $this->projectMaxHoursBlockRemain($task, $start);

            if (
                $slot_key != -1
                && $project_remaining_time_for_day > 0
                && $project_remaining_time_for_block > 0
            ) {
                $this->addTaskToSlot($task, $slot_key, $start, $end);
            }
        }

        if ($task['time_remaining_minutes'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the max hours for this task is already reached for
     * this day. Calculate how much time could be planned for this
     * project on this day. If this is 0, it basically means ...
     * no time left, obvisouly.
     *
     * @param  array &$task
     * @return int
     */
    public function projectMaxHoursDayRemain(&$task)
    {
        $max_minutes_day = (int) round($task['project_max_hours_day'] * 60);

        $project_id = (int) $task['project_id'];
        if (!array_key_exists($project_id, $this->planned_time_per_project_ids)) {
            $this->planned_time_per_project_ids[$project_id] = 0;
        }
        return $max_minutes_day - $this->planned_time_per_project_ids[$project_id];
    }

    /**
     * Check if the max hours for this task is already reached for
     * consecutive blocks. Means: is there a block for this project
     * before the next-to-be-planned block? If so: how much time
     * would be left? If there is no block, technically the max
     * can be used.
     *
     * @param  array &$task
     * @param  int   $start
     * @return int
     */
    public function projectMaxHoursBlockRemain(&$task, $start)
    {
        $max_minutes_block = (int) round($task['project_max_hours_block'] * 60);
        $project_id = $task['project_id'];
        if ($this->planned_time_block['project_id'] == $project_id) {
            return $max_minutes_block - $this->planned_time_block['time'];
        } else {
            return $max_minutes_block;
        }
    }

    /**
     * With the given task and the given slot calculate a
     * possible end time for the task to be planned.
     *
     * E.g. the end cannot exceed the slots end time.
     * Also the method calculates the possible max time
     * for the task to be planned, according to the
     * project info.
     *
     * @param  array $task
     * @param  array $slot
     * @return int
     */
    public function calculateEndOfSlot($task, $slot)
    {
        $max_minutes_block = (int) round($task['project_max_hours_block'] * 60);
        $remaining_time_of_task = $task['time_remaining_minutes'];
        if ($remaining_time_of_task > $max_minutes_block) {
            $remaining_time_of_task = $max_minutes_block;
        }
        if ($remaining_time_of_task > $slot['remain']) {
            $remaining_time_of_task = $slot['remain'];
        }
        return $slot['next'] + $remaining_time_of_task;
    }

    /**
     * Initialize additional keys with values for the given task.
     * They will be used by the "Automatic Planner" feature.
     *
     * @param  array &$task
     */
    public function initAdditionalTaskValues(&$task)
    {
        // if this key does not exist, all other should also not exist
        // and thus should be "initialized"
        if (!array_key_exists('time_remaining_minutes', $task)) {
            // this value is needed for the overall planning in comparison to
            // all other tasks as well. That's why it has to be set in the
            // referenced task so that it will be available again in another
            // call of the planTask() method later on.
            // it is basically the remaining time for the task in minutes. also
            // this value will be modified for the global task item after adding.
            $task['time_remaining_minutes'] = (int) round((float) $task['time_remaining'] * 60);
        }
    }

    /**
     * Finally add a task to the internal tasks array and update the
     * internal slot array.
     *
     * @param array $task
     * @param int $slot_key
     * @param int $start
     * @param int $end
     */
    public function addTaskToSlot(&$task, $slot_key, $start, $end)
    {
        // prepare values and add the task
        $length = $end - $start;
        $prepared_task = array_merge($task, [
            'timeslotday_start' => $start,
            'timeslotday_end' => $end,
            'timeslotday_length' => $length,
        ]);
        $this->tasks[] = $prepared_task;

        // update the tasks internal values; this is needed, in case
        // the task will be processed by another TimeSlotDay instance.
        $task['time_remaining_minutes'] -= $length;

        // update the internal slots
        $this->slots[$slot_key]['start'] += $length;
        $this->slots[$slot_key]['next'] += $length;
        $this->slots[$slot_key]['remain'] -= $length;

        // update the internal values, which are important for the
        // "project_max_hours_day" and "project_max_hours_block"
        // project info.
        $project_id = $task['project_id'];
        if (!array_key_exists($project_id, $this->planned_time_per_project_ids)) {
            $this->planned_time_per_project_ids[$project_id] = $length;
        } else {
            $this->planned_time_per_project_ids[$project_id] += $length;
        }
        if ($this->planned_time_block['project_id'] == $project_id) {
            // check if there is a last planned task at all
            if (count($this->tasks) >= 2) {
                // check if the planned task before this one
                // is of the same project and if its end time
                // is apart <= 5 minutes from this newly added
                // tasks start time. in that case it would be
                // considered as the same block and the time
                // would be added to the block cache count.
                // otherwise it would be considered to be a new
                // block and the length is the "fresh start" of
                // the new block.
                $other_project_id = $this->tasks[count($this->tasks) - 2]['project_id'];
                $other_end_time = $this->tasks[count($this->tasks) - 2]['timeslotday_end'];
                if (
                    $other_project_id == $project_id
                    && $start - $other_end_time <= 5
                ) {
                    $this->planned_time_block['time'] += $length;
                } else {
                    $this->planned_time_block['time'] = $length;
                }
            } else {
                // no last planned task means it is considered a new
                // fresh block start
                $this->planned_time_block['time'] = $length;
            }
        } else {
            $this->planned_time_block['project_id'] = $project_id;
            $this->planned_time_block['time'] = $length;
        }
    }

    /**
     * Return the internal tasks array, which should contain all the
     * planned tasks!
     *
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }
}
