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
}

