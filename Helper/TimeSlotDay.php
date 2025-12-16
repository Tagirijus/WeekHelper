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
     * The left minutes available to plan for this day.
     *
     * @var int
     **/
    var $available_time = 0;

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
        $this->available_time = 0;
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

            // also add to the overall left time contingent
            $this->available_time += $end - $start;
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

        // maybe there is not even a slot planned at all yet?
        if (empty($this->tasks)) {
            return $max_minutes_block;
        }

        // if the last planned project is not the same
        if (end($this->tasks)['project_id'] != $task['project_id']) {
            return $max_minutes_block;

        } else {
            // maybe the last project is the same; check if the end time
            // of it is greater than 5 minutes; then it's okay, I'd say
            if ($start - end($this->tasks)['timeslotday_end'] > 5) {
                return $max_minutes_block;
            } else {
                // otherwise get the difference, which could still be planned
                return $max_minutes_block - end($this->tasks)['timeslotday_length'];
            }
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
        $length = $end - $start;
        $prepared_task = array_merge($task, [
            'timeslotday_start' => $start,
            'timeslotday_end' => $end,
            'timeslotday_length' => $length,
        ]);
        $this->tasks[] = $prepared_task;

        $task['time_remaining_minutes'] -= $length;

        $this->slots[$slot_key]['start'] += $length;
        $this->slots[$slot_key]['next'] += $length;
        $this->slots[$slot_key]['remain'] -= $length;

        $this->available_time -= $length;
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
