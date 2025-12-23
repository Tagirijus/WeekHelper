<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TimeSpan;


class TimeSlotsDay
{
    /**
     * The day this instance is for.
     *
     * @var string
     **/
    var $day = 'mon';

    /**
     * All slots for this day. This contains the set times,
     * the still-available times and their type, of course.
     * Basically mainly managed through the TimeSpan class.
     *
     * A slot might have the following structure:
     *     [
     *         'timespan' => TimeSpan instance,
     *         'project_type' => type of this slot or empty string for ALL
     *     ]
     *
     * @var array
     **/
    var $slots = [];

    /**
     * Initialize a time slot day instance with the given
     * raw config string for this day.
     *
     * @param string $config_string
     * @param string $day
     */
    public function __construct($config_string, $day = 'mon')
    {
        $this->initSlots($config_string);
        $this->day = $day;
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
        $lines = explode("\n", $config_string ?? '');
        foreach ($lines as $line) {
            // splitting initial config string into times and project_type
            $parts = preg_split('/\s+/', $line);
            $times = $parts[0];
            if (count($parts) > 1) {
                $project_type = $parts[1];
            } else {
                $project_type = '';
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
                'timespan' => new TimeSpan($start, $end),
                'project_type' => trim($project_type)
            ];
        }

        // sort the slots on their start time, in case
        // the user entered the slots in a wild order
        usort($this->slots, function ($a, $b) {
            if ($a['timespan']->getStart() == $b['timespan']->getStart()) {
                return 0;
            }
            return ($a['timespan']->getStart() < $b['timespan']->getStart()) ? -1 : 1;
        });
    }

    /**
     * Return the internal slots.
     *
     * @return array
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * Return the day this time slots day instance is for.
     *
     * @return string
     */
    public function getDay()
    {
        return $this->day;
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
     * @param  string $project_type
     * @return int
     */
    public function nextSlot($project_type = '')
    {
        foreach ($this->slots as $key => $slot) {
            // if a slot type is empty, it means that every project_type
            // may be planned here!
            $type_is_valid = (
                $project_type == ''
                || $slot['project_type'] == ''
                || $slot['project_type'] == $project_type
            );
            $has_remaining_time = $slot['timespan']->length() > 0;
            if ($type_is_valid && $has_remaining_time) {
                return $key;
            }
        }
        return -1;
    }

    /**
     * Plan anything on the given slot from either a given
     * start for the given length or maybe just the length,
     * which will plan it automatically onto the next possible
     * start in this slot.
     *
     * This will basically "reduce" the available slot time
     * and maybe even deplete the slot.
     *
     * Returns an empty string, when planning went as expected.
     * Otherwise with a string describing, what happened instead.
     *
     * @param  integer $slot_key
     * @param  integer $length
     * @param  integer $start
     *
     * @return string
     */
    public function planTime($slot_key, $length, $start = -1)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            $timespan = &$this->slots[$slot_key]['timespan'];
            if ($start == -1) {
                $start = $timespan->getStart();
            }
            $start_is_in_span = $timespan->isIn($start);
            $length_fits_in = $timespan->length() >= $length;
            if ($start_is_in_span && $length_fits_in) {
                $timespan->setStart($start + $length);
            } elseif (!$start_is_in_span && $length_fits_in) {
                return 'Start is not in time span.';
            } elseif ($start_is_in_span && !$length_fits_in) {
                return 'Length does not fit into time span.';
            } else {
                return 'Start is not in time span and length does not fit into slot.';
            }
        } else {
            return 'Slot "' . $slot_key . '" does not exist.';
        }
        return '';
    }

    /**
     * Return the start for the wanted slot.
     *
     * @param  integer $slot_key
     * @return integer
     */
    public function getStartOfSlot($slot_key)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            return $this->slots[$slot_key]['timespan']->getStart();
        } else {
            return -1;
        }
    }

    /**
     * Return the length for the wanted slot.
     *
     * @param  integer $slot_key
     * @return integer
     */
    public function getLengthOfSlot($slot_key)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            return $this->slots[$slot_key]['timespan']->length();
        } else {
            return -1;
        }
    }

    /**
     * Return the end for the wanted slot.
     *
     * @param  integer $slot_key
     * @return integer
     */
    public function getEndOfSlot($slot_key)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            return $this->slots[$slot_key]['timespan']->getEnd();
        } else {
            return -1;
        }
    }

    /**
     * Deplete the given slot. Return if succeeded.
     *
     * @param  integer $slot_key
     * @return boolean
     */
    public function depleteSlot($slot_key)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            $this->slots[$slot_key]['timespan']->deplete();
            return true;
        } else {
            return false;
        }
    }
}
