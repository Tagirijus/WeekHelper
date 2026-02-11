<?php

/**
 * A TimeSpan instance can hold a timespan with its start and end.
 * They are supposed to stand for minutes. It can also return the
 * length of a span or give info about a given single timepoint
 * (which is an integer basically) and if this point is in the
 * span, or what the difference to start / end is, etc.
 *
 * I use this class for the automatic planenr distribution logic
 * and the timeslots.
 */

namespace Kanboard\Plugin\WeekHelper\Model;


class TimeSpan
{
    /**
     * The start value.
     *
     * @var integer
     **/
    var $start = 0;

    /**
     * The end value.
     *
     * @var integer
     **/
    var $end = 0;

    /**
     * Initialize the timespan with the two important values
     * for start and end.
     *
     * @param integer $start
     * @param integer $end
     */
    public function __construct($start = 0, $end = 0)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Depletes the time span to make it have no "time resources"
     * anymore. Technically it will just set the start to the end.
     */
    public function deplete()
    {
        $this->setStart($this->getEnd());
    }

    /**
     * Calculate the difference of the given value relative to
     * the start value.
     *
     * Negative output means the given time is before, positive
     * means it's after.
     *
     * @param  integer $time
     * @return integer
     */
    public function diffToStart($time)
    {
        return $time - $this->start;
    }

    /**
     * Calculate the difference of the given value relative to
     * the end value.
     *
     * Negative output means the given time is before, positive
     * means it's after.
     *
     * @param  integer $time
     * @return integer
     */
    public function diffToEnd($time)
    {
        return $time - $this->end;
    }

    /**
     * Get the end.
     *
     * @return integer
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get the start.
     *
     * @return integer
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Checks if the given value is in before the time span.
     * Means: is it < start
     *
     * Info: the time is supposed to be a minutes number.
     *
     * @param  integer  $time
     * @return boolean
     */
    public function isBefore($time)
    {
        return $time < $this->start;
    }

    /**
     * Checks if the given value is in the time span.
     * Means: is it >= start and < end?
     *
     * Info: the time is supposed to be a minutes number.
     *
     * @param  integer  $time
     * @return boolean
     */
    public function isIn($time)
    {
        return $time >= $this->start && $time < $this->end;
    }

    /**
     * Calculate the length of the time span, which basically
     * will just calculate end - start.
     *
     * @return integer
     */
    public function length()
    {
        return $this->end - $this->start;
    }

    /**
     * Set the end.
     *
     * @param integer $end
     */
    public function setEnd($end = 0)
    {
        $this->end = (int) $end;
    }

    /**
     * Set the start.
     *
     * @param integer $start
     */
    public function setStart($start = 0)
    {
        $this->start = (int) $start;
    }

    /**
     * Check if a given TimePoint instance is in this TimeSpan.
     *
     * @param  TimePoint $time_point
     * @return boolean
     */
    public function timepointIsIn($time_point)
    {
        return (
            $time_point->getTime() >= $this->start
            && $time_point->getTime() < $this->end
        );
    }
}
