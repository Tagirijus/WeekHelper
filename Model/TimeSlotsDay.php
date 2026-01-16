<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Model\TimeSpan;
use Kanboard\Plugin\WeekHelper\Model\TimePoint;
use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


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
     *         'timespan_init' => TimeSpan instance (with initialized values),
     *         'conditions_allow' => array for the readable conditions for "allow",
     *         'conditions_refuse' => array for the readable conditions for "refuse",
     *         'conditions_set' => [
     *             'allow' => array with the conditions for internal checkings,
     *             'refuse' => array with the conditions for internal checkings,
     *         ]
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
        foreach ($lines as &$line) {

            $line = trim($line);
            if ($line == '') {
                continue;
            }

            // splitting initial config string into times and conditions
            $parts = preg_split('/\s+/', $line, 2);
            $times = $parts[0];
            if (count($parts) > 1) {
                $conditions = trim($parts[1]);
            } else {
                $conditions = '';
            }

            // now splitting times into start and end
            $times_parts = preg_split('/\-/', $times);
            if (count($times_parts) == 2) {
                $start = TimeHelper::readableToMinutes($times_parts[0]);
                $end = TimeHelper::readableToMinutes($times_parts[1]);
            } else {
                $start = 0;
                $end = 0;
            }

            // now create the conditions
            [$conditions_allow, $conditions_refuse] = self::parseConditionsString($conditions);

            // add a time slot finally
            $this->slots[] = [
                'timespan' => new TimeSpan($start, $end),
                'timespan_init' => new TimeSpan($start, $end),
                'conditions_allow' => $conditions_allow,
                'conditions_refuse' => $conditions_refuse,
                'conditions_set' => self::prepareConditionsSet([
                    'allow' => $conditions_allow,
                    'refuse' => $conditions_refuse
                ]),
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
     * Make a set out of the given conditions array for later
     * better checking against a test array. This should avoid
     * unneccessary iteration loops.
     *
     * @param  array $conditions
     * @return array
     */
    public static function prepareConditionsSet($conditions)
    {
        $out = ['allow' => [], 'refuse' => []];
        foreach (['allow','refuse'] as $mode) {
            foreach ($conditions[$mode] ?? [] as $k => $vals) {
                $out[$mode][$k] = array_fill_keys($vals, true);
            }
        }
        return $out;
    }

    /**
     * Parse a given conditions string from the timeslots config.
     * It's basically a whitespace separated array, which can hold
     * an array key or not. If not, "project_type" is used for backwards
     * compabilities.
     * It returns and array for conditions_allow and conditions_refuse:
     *   [conditions_allow, conditions_refuse]
     *
     * @param  string $conditions
     * @return array
     */
    public static function parseConditionsString($conditions = '')
    {
        if ($conditions == '') {
            return [[], []];
        }
        $allow = [];
        $refuse = [];
        $parts = preg_split('/\s+/', $conditions);
        foreach ($parts as $part) {
            $split = explode(':', $part);
            // if no colon is given, it's the old behaviour, which
            // was "project_type" only, basically.
            $key = count($split) == 2 ? $split[0] : 'project_type';
            $value = count($split) == 2 ? $split[1] : $split[0];
            // now add it to either allow or refuse, depending
            // on a prefix "!".
            $prefix = $value[0] != '!' ? 'allow' : 'refuse';
            $value  = ltrim($value, '!');
            // multiple options for a key should be possible
            if (!array_key_exists($key, ${$prefix})) {
                ${$prefix}[$key] = [];
            }
            ${$prefix}[$key][] = $value;
        }
        return [$allow, $refuse];
    }

    /**
     * Return the internal slots or even just a single
     * one with the given slot key.
     *
     * @param integer|null  $slot_key
     * @return array
     */
    public function getSlots($slot_key = null)
    {
        if (is_null($slot_key)) {
            return $this->slots;
        } else {
            if (isset($this->slots[$slot_key])) {
                return $this->slots[$slot_key];
            } else {
                return $this->slots;
            }
        }
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
     * Find the next slot. This function retuns -1, if no
     * slot was found (maybe, because no time left for the day).
     * Otherwise it will return the key of the internal slot array
     * for the slot, which still has remaining time to plan left.
     *
     * The given condition parameter can be a single string, which
     * stands for a "project_type" key (old behaviour) or it should
     * rather be an array like the task array. It's keys and values
     * will be checked against the internal timeslots "conditions_allow"
     * and "conditions_refuse" array.
     *
     * @param  string|array $condition
     * @param  string $time_point_str
     * @return int
     */
    public function nextSlot($condition = '', $time_point_str = '')
    {
        foreach ($this->slots as $key => $slot) {
            $has_remaining_time = $slot['timespan']->length() > 0;
            $time_point = new TimePoint($time_point_str);
            $time_point_str_is_in_or_before = (
                // either there is no timepoint string given
                $time_point_str == ''
                // or the timepoint is exactly in the slot
                || (
                    $slot['timespan']->timepointIsIn($time_point)
                    && $time_point->getDay() == $this->getDay()
                )
                // or the timepoint is before the next available slots
                || (
                    $slot['timespan']->getStart() >= $time_point->getTime()
                    && TimeHelper::diffOfWeekDays(
                        $this->getDay(), $time_point->getDay()
                    ) < 1
                )
            );
            if (
                self::slotConditionCheck($slot, $condition)
                && $has_remaining_time
                && $time_point_str_is_in_or_before
            ) {
                return $key;
            }
        }
        return -1;
    }

    /**
     * The check, which checks for the given slot with the given
     * test (probably a task array), if the task could be
     * planned on that slot according to the slots conditions.
     *
     * @param  array $slot
     * @param  string|array $test
     * @return boolean
     */
    public static function slotConditionCheck($slot, $test)
    {
        if (empty($test)) {
            return true;
        }

        // backwards compability: earlier if only a string was
        // given for the check, it was supposed to be a check
        // for the "project_type" key only
        if (is_string($test)) {
            $test = ['project_type' => $test];
        }

        $allow = 0;

        foreach ($test as $key => $value) {
            // refuse condition has priority. first possible refusion
            // will return false immediately due to that priority; and
            // this can skip all the other tests and the loop
            if (isset($slot['conditions_set']['refuse'][$key][$value])) {
                return false;
            }

            // no allow conditions set in slot, which means: if so far
            // no refuse condition was met, everything else is allowed
            if (empty($slot['conditions_set']['allow'])) {
                $allow++;
                continue;
            }

            // or at least the key does not exist at all, which means
            // from the perspective of "allowing" that all such
            // kinds are allowed (at least this shall be my logic here)
            if (!isset($slot['conditions_set']['allow'][$key])) {
                $allow++;
                continue;
            }

            // otherwise a check for allowed condition is needed with
            // the given value
            if (isset($slot['conditions_set']['allow'][$key][$value])) {
                $allow++;
                continue;
            }

            // maybe the key does exist in "allow" but does not contain
            // the value, which would result in a refuse, basically
            if (
                isset($slot['conditions_set']['allow'][$key])
                && !array_key_exists($value, $slot['conditions_set']['allow'][$key])
            ) {
                return false;
            }

        }

        return $allow !== 0;
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
     * With $init_value set to "true", the initial values
     * for the internal TimeSpan of this slot should be used.
     * That way it does not matter how much there is left or
     * if the slot is depleted - it will just return the original
     * start of the slot.
     *
     * @param  integer $slot_key
     * @param  boolean $init_value
     * @return integer
     */
    public function getStartOfSlot($slot_key, $init_value = false)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            if ($init_value) {
                return $this->slots[$slot_key]['timespan_init']->getStart();
            } else {
                return $this->slots[$slot_key]['timespan']->getStart();
            }
        } else {
            return -1;
        }
    }

    /**
     * Return the length for the wanted slot.
     *
     * With $init_value set to "true", the initial values
     * for the internal TimeSpan of this slot should be used.
     * That way it does not matter how much there is left or
     * if the slot is depleted - it will just return the original
     * length of the slot.
     *
     * @param  integer $slot_key
     * @param  boolean $init_value
     * @return integer
     */
    public function getLengthOfSlot($slot_key, $init_value = false)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            if ($init_value) {
                return $this->slots[$slot_key]['timespan_init']->length();
            } else {
                return $this->slots[$slot_key]['timespan']->length();
            }
        } else {
            return -1;
        }
    }

    /**
     * Return the end for the wanted slot.
     *
     * With $init_value set to "true", the initial values
     * for the internal TimeSpan of this slot should be used.
     * That way it does not matter how much there is left or
     * if the slot is depleted - it will just return the original
     * end of the slot.
     *
     * @param  integer $slot_key
     * @param  boolean $init_value
     * @return integer
     */
    public function getEndOfSlot($slot_key, $init_value = false)
    {
        if (array_key_exists($slot_key, $this->slots)) {
            if ($init_value) {
                return $this->slots[$slot_key]['timespan_init']->getEnd();
            } else {
                return $this->slots[$slot_key]['timespan']->getEnd();
            }
        } else {
            return -1;
        }
    }

    /**
     * Simply deplete all the slots completely for this day.
     */
    public function deplete()
    {
        foreach ($this->slots as &$slot) {
            $slot['timespan']->deplete();
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

    /**
     * Deplete slots by time span. This ultimately can
     * generate new time slots, in case the span would
     * cut a slot in half, basically.
     *
     * @param  TimeSpan $time_span
     * @return boolean
     */
    public function depleteByTimeSpan($time_span)
    {
        try {
            $new_slots = [];
            foreach ($this->slots as $slot) {
                $slot_updated = self::depleteSingleSlotByTimeSpan($slot, $time_span);
                $new_slots = array_merge($new_slots, $slot_updated);
            }
            $this->slots = $new_slots;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Basically helper method of the parent method with
     * almost the same name. This one will simply modify
     * only the given slot with the given slot key.
     *
     * It will return an array holding the modified slot,
     * or even multiple, in case it got split into half.
     *
     * @param  array $slot
     * @param  TimeSpan $time_span
     * @return array
     */
    public static function depleteSingleSlotByTimeSpan($slot, $time_span)
    {
        // time span is not in the slot at all and won't do anything
        if (
            $time_span->getEnd() < $slot['timespan']->getStart()
            || $time_span->getStart() > $slot['timespan']->getEnd()
        ) {
            return [$slot];

        // time span will only cut out something from the start
        } elseif (
            $time_span->getStart() <= $slot['timespan']->getStart()
            && $time_span->getEnd() > $slot['timespan']->getStart()
            && $time_span->getEnd() < $slot['timespan']->getEnd()
        ) {
            $slot['timespan']->setStart($time_span->getEnd());
            return [$slot];

        // time span will only cut out something from the end
        } elseif (
            $time_span->getStart() > $slot['timespan']->getStart()
            && $time_span->getStart() < $slot['timespan']->getEnd()
            && $time_span->getEnd() >= $slot['timespan']->getEnd()
        ) {
            $slot['timespan']->setEnd($time_span->getStart());
            return [$slot];

        // time span will cut the slot into half
        } elseif (
            $time_span->getStart() > $slot['timespan']->getStart()
            && $time_span->getStart() < $slot['timespan']->getEnd()
            && $time_span->getEnd() > $slot['timespan']->getStart()
            && $time_span->getEnd() < $slot['timespan']->getEnd()
        ) {
            $slot_a = [
                'timespan' => new TimeSpan(
                    $slot['timespan']->getStart(),
                    $time_span->getStart()
                ),
                'conditions_allow' => $slot['conditions_allow'],
                'conditions_refuse' => $slot['conditions_refuse'],
                'conditions_set' => $slot['conditions_set']
            ];
            $slot_b = [
                'timespan' => new TimeSpan(
                    $time_span->getEnd(),
                    $slot['timespan']->getEnd()
                ),
                'conditions_allow' => $slot['conditions_allow'],
                'conditions_refuse' => $slot['conditions_refuse'],
                'conditions_set' => $slot['conditions_set']
            ];
            return [$slot_a, $slot_b];

        // time span is exactly the slot or around it; make it depleted
        } elseif (
            $time_span->getStart() <= $slot['timespan']->getStart()
            && $time_span->getEnd() >= $slot['timespan']->getEnd()
        ) {
            $slot['timespan']->deplete();
            return [$slot];

        // fallback: do nothing ...
        } else {
            return [$slot];
        }
    }

    /**
     * It is possible to deplete a TimeSlotsDay by a given
     * TimePoint, which will internally be converted to a
     * TimeSpan like start of the day till the TimePoint time.
     * So if the TimePoint should represent 13:00 o'clock, it
     * would deplete the TimeSpan of 0:00-13:00.
     *
     * ignore_day cna be set to true so that a TimePoint with
     * a different day than the internal TimeSlotsDay can
     * also be used.
     *
     * @param  TimePoint $time_point
     * @param  boolean   $ignore_day
     * @return boolean
     */
    public function depleteByTimePoint($time_point, $ignore_day = false)
    {
        $time_span = new TimeSpan(0, $time_point->getTime());
        if ($this->day == $time_point->getDay() || $ignore_day) {
            return $this->depleteByTimeSpan($time_span);
        } else {
            return false;
        }
    }

    /**
     * Check if the given TimePoint instance is in any of the momentary
     * slots and return the slot_key then. Returns -1 if given TimePoint
     * is not in any of these slots.
     *
     * @param  TimePoint $time_point
     * @return integer
     */
    public function slotKeyFromTimePoint($time_point)
    {
        if ($this->day == $time_point->getDay()) {
            foreach ($this->slots as $key => $slot) {
                if ($slot['timespan']->timepointIsIn($time_point)) {
                    return $key;
                }
            }
        }
        return -1;
    }

    /**
     * Return the difference of days for the given TimePoint
     * from the internal days. This method will create another
     * pseudo TimePoint temporarily for this check.
     *
     * Positive numbers mean that the given TimePoint is after
     * the internal day, negative means is is before, 0 means
     * it is the same day.
     *
     * @param  TimePoint $time_point
     * @return integer
     */
    public function dayDiffFromTimePoint($time_point)
    {
        $tmp_timepoint = new TimePoint($this->day . ' 0:00');
        return $tmp_timepoint->dayDiffFromTimePoint($time_point);
    }

    /**
     * Return the overall length of the day. With init_value
     * ste to "true" it will use the original TimeSpans of
     * the slots.
     *
     * @param  boolean $init_value
     * @return integer
     */
    public function getLength($init_value = false)
    {
        $out = 0;
        foreach ($this->slots as $key => $slot) {
            $out += $this->getLengthOfSlot($key, $init_value);
        }
        return $out;
    }

    /**
     * Split an internal slot into two slots, if the given
     * TimePoint is inside this slot. True, if splitting
     * was successful, otherwise false.
     *
     * @param  TimePoint $time_point
     * @return boolean
     */
    public function splitSlotByTimepoint($time_point)
    {
        $success = false;
        $slot_key = $this->slotKeyFromTimePoint($time_point);
        if ($slot_key != -1) {
            $new_slots = [];
            foreach ($this->getSlots() as $key => $slot) {
                if ($slot_key == $key) {
                    $first_half = [
                        'timespan' => new TimeSpan(
                            $slot['timespan']->getStart(),
                            $time_point->getTime()
                        ),
                        'timespan_init' => new TimeSpan(
                            $slot['timespan_init']->getStart(),
                            $time_point->getTime()
                        ),
                        'conditions_allow' => $slot['conditions_allow'],
                        'conditions_refuse' => $slot['conditions_refuse'],
                        'conditions_set' => $slot['conditions_set']
                    ];
                    $second_half = [
                        'timespan' => new TimeSpan(
                            $time_point->getTime(),
                            $slot['timespan']->getEnd()
                        ),
                        'timespan_init' => new TimeSpan(
                            $time_point->getTime(),
                            $slot['timespan_init']->getEnd()
                        ),
                        'conditions_allow' => $slot['conditions_allow'],
                        'conditions_refuse' => $slot['conditions_refuse'],
                        'conditions_set' => $slot['conditions_set']
                    ];
                    $new_slots[] = $first_half;
                    $new_slots[] = $second_half;
                } else {
                    $new_slots[] = $slot;
                }
            }
            $this->slots = $new_slots;
            $success = true;
        }
        return $success;
    }

    /**
     * Basically a wrapper for the splitSlotByTimepoint() method,
     * but this one can get a string, which will be converted
     * to a TimePoint internally.
     *
     * @param  string $time_point_str
     * @return boolean
     */
    public function splitSlotByTimepointString($time_point_str = '')
    {
        if ($time_point_str == '') {
            return false;
        }
        $time_point = new TimePoint($time_point_str);
        return $this->splitSlotByTimepoint($time_point);
    }
}
