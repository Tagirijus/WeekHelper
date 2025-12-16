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
     *
     * @var array
     **/
    var $tasks = [];

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

        // TODO / WEITER HIER
        // Planungs-Algorithus hier etablieren!

        if ($task['_timeslotday_remaining'] == 0) {
            return true;
        } else {
            return false;
        }
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
        if (!array_key_exists('_timeslotday_remaining', $task)) {
            $task['_timeslotday_remaining'] = (int) round((float) $task['time_remaining'] * 60);
        }
    }

    /**
     * Return the internal tasks array, which should contain all the
     * (referenced) planned tasks!
     *
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }
}
