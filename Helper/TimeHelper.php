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
}

