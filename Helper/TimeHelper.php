<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

/**
 * Some static time helper functions.
 */
class TimeHelper
{
    /**
     * Convert the given float to rounded minutes.
     *
     * @param  float $hours
     * @return integer
     */
    public static function hoursToMinutes($hours)
    {
        return (int) round($hours * 60);
    }

    /**
     * Convert the given readbale time string into full minutes.
     *
     * @param  string $readable
     * @return integer
     */
    public static function readableToMinutes($readable = "")
    {
        if (!$readable) {
            return 0;
        } else {
            list($hours, $minutes) = array_map('intval', explode(':', $readable, 2));
            return ($hours * 60) + $minutes;
        }
    }

    /**
     * Calculate the day difference from day 1 to day 2 with
     * only the abbreviation of the day given in lower case
     * or upper case. Normal day names are also possible.
     *
     * Base is day 1. So if day 2 lies in the past compared
     * to day 1, it will return a negative  number.
     *
     * Example:
     * day1 = 'wed' and day2 = 'mon' will return -2.
     *
     * Also there is the special virtual day "overflow" or
     * "ovr" available, which technically lies after sunday.
     *
     * @param  string $day1
     * @param  string $day2
     * @return integer
     */
    public static function diffOfWeekDays($day1 = 'mon', $day2 = 'mon')
    {
        $normalize = function(string $d): string {
            $d = strtolower($d);
            return $d === 'overflow' ? 'ovr' : substr($d, 0, 3);
        };

        static $map = [
            'mon' => 0, 'tue' => 1, 'wed' => 2,
            'thu' => 3, 'fri' => 4, 'sat' => 5,
            'sun' => 6, 'ovr' => 7,
        ];

        $a = $normalize($day1);
        $b = $normalize($day2);

        if (!isset($map[$a]) || !isset($map[$b])) {
            throw new \InvalidArgumentException("Invalid weekday: {$day1} or {$day2}");
        }

        return $map[$b] - $map[$a];
    }
}

