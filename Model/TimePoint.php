<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


/**
 * A time point is for converting dates and checkign against
 * TimeSlotsDay instances (maybe even TimeSpan, but then the
 * day attribute is not used).
 *
 * The idea behind this is to have rather some kind of relative
 * time stuff going on instead of absolute dates. I want to
 * plan my week (this and next week). For that I only want to
 * work with days and times on these days. Since it's always
 * just about a single week, I only need the weekdays and their
 * times of the day to work with. Also I am only working with
 * one week separately (this week OR the next). So it's always
 * just about one single week.
 */
class TimePoint
{
    /**
     * The day it is about.
     *
     * @var string
     **/
    var $day = 'mon';

    /**
     * The minutes of the day is it about.
     *
     * @var integer
     **/
    var $time = 0;

    /**
     * Initialize the instance with a time string, which should
     * have the weekday (english short) a whitespace and the time
     * of the day in the format "[H]H:MM".
     *
     * If the given time_string is left blank, it will use "now"
     * as the time.
     *
     * @param string $time_string
     */
    public function __construct($time_string = '')
    {
        if (empty($time_string)) {
            $time_string = date('D G:i');
        }
        $this->setTimePointFromString($time_string);
    }

    /**
     * Set the internal values from a given string.
     *
     * @param string $time_string
     */
    public function setTimePointFromString($time_string)
    {
        try {
            $parts = preg_split('/\s+/', $time_string);
            $this->day = strtolower($parts[0]);
            $this->time = TimeHelper::readableToMinutes($parts[1]);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Return the day of this TimePoint.
     *
     * @return string
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Return the time of this TimePoints, which are
     * basically the minutes on that day. So 6:00
     * woudl be 360.
     *
     * @return integer
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Check against another TimePoint instance.
     *
     * @param  TimePoint
     * @return boolean
     */
    public function isSame($time_point)
    {
        $day_same = $this->getDay() == $time_point->getDay();
        $time_same = $this->getTime() == $time_point->getTime();
        return $day_same && $time_same;
    }

    /**
     * Return the number of days the given TimePoint
     * day is away form this instances day. Positive
     * number means it's after this day, negative means
     * it is before. 0 means it is also today.
     *
     * @param  TimePoint $time_point
     * @return integer
     */
    public function dayDiffFromTimePoint($time_point)
    {
        return TimeHelper::diffOfWeekDays($this->day, $time_point->getDay());
    }
}
