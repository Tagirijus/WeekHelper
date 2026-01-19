<?php

namespace Kanboard\Plugin\WeekHelper\Model;


/**
 * With this class I will have some base times class, which can
 * hold a "times array".
 *
 * There might be super-classes using this class for single
 * items, while storing them on a certain entity (e.g. like the
 * task_id, project_id, user_id, level string, etc.).
 */
class TimesData
{
    /**
     * The basic times array. Classes, which will inherit
     * from this class can overwrite this core attribute,
     * but should also adjust the methods, below, which
     * might change this attribute (e.g. like adder, getter,
     * etc.).
     *
     * @var array
     **/
    protected $times;

    /**
     * The times array, but with readbale strings instead of
     * the numeric values.
     *
     * @var array
     */
    protected $times_readable;

    /**
     * Instanciate this class.
     */
    public function __construct()
    {
        $this->times = self::emptyTimesArray();
    }

    /**
     * Add the given floats to the internal core times data attribute.
     *
     * @param float $estimated
     * @param float $spent
     * @param float $remaining
     * @param float $overtime
     */
    public function addTimes(
        $estimated,
        $spent,
        $remaining,
        $overtime
    )
    {
        $this->addTimesToArray(
            $this->times,
            $estimated,
            $spent,
            $remaining,
            $overtime
        );
        $this->times_readable = self::toReadable($this->times);
    }

    /**
     * Add the given floats to the internal core times data attribute.
     *
     * @param array &$arr
     * @param float $estimated
     * @param float $spent
     * @param float $remaining
     * @param float $overtime
     */
    protected static function addTimesToArray(
        &$arr,
        $estimated,
        $spent,
        $remaining,
        $overtime
    )
    {
        $arr['estimated'] += $estimated;
        $arr['spent'] += $spent;
        $arr['remaining'] += $remaining;
        $arr['overtime'] += $overtime;

        // also update the has_times boolean
        $arr['has_times'] = (
            $arr['estimated'] != 0.0
            || $arr['spent'] != 0.0
            || $arr['remaining'] != 0.0
            || $arr['overtime'] != 0.0
        );
    }

    /**
     * The default empty times array.
     *
     * @return array
     */
    public static function emptyTimesArray()
    {
        return [
            'has_times' => false,
            'estimated' => 0.0,
            'spent' => 0.0,
            'remaining' => 0.0,
            'overtime' => 0.0,
        ];
    }

    /**
     * Represent the given float as a proper time string.
     *
     * @param  float $time
     * @return string
     */
    public static function floatToHHMM($time)
    {
        if ($time < 0) {
            $time = $time * -1;
            $negative = true;
        } else {
            $negative = false;
        }
        $hours = (int) $time;
        $minutes = fmod((float) $time, 1) * 60;
        if ($negative) {
            return sprintf('-%01d:%02d', $hours, $minutes);
        } else {
            return sprintf('%01d:%02d', $hours, $minutes);
        }
    }

    /**
     * Get the the estimated time.
     *
     * @param boolean $readable
     * @return float
     */
    public function getEstimated($readable = false)
    {
        if ($readable) {
            return $this->times_readable['estimated'];
        } else {
            return $this->times['estimated'];
        }
    }

    /**
     * Get the the overtime time.
     *
     * @param boolean $readable
     * @return float
     */
    public function getOvertime($readable = false)
    {
        if ($readable) {
            return $this->times_readable['overtime'];
        } else {
            return $this->times['overtime'];
        }
    }

    /**
     * Get the the remaining time.
     *
     * @param boolean $readable
     * @return float
     */
    public function getRemaining($readable = false)
    {
        if ($readable) {
            return $this->times_readable['remaining'];
        } else {
            return $this->times['remaining'];
        }
    }

    /**
     * Get the the spent time.
     *
     * @param boolean $readable
     * @return float
     */
    public function getSpent($readable = false)
    {
        if ($readable) {
            return $this->times_readable['spent'];
        } else {
            return $this->times['spent'];
        }
    }

    /**
     * Return, if there are times at all.
     *
     * @return boolean
     */
    public function hasTimes()
    {
        return $this->times['has_times'];
    }

    /**
     * Convert the given times array to a readable.
     *
     * @param  array $times_array
     * @return array
     */
    protected static function toReadable($times_array)
    {
        return [
            'has_times' => $times_array['has_times'],
            'estimated' => self::floatToHHMM($times_array['estimated']),
            'spent' => self::floatToHHMM($times_array['spent']),
            'remaining' => self::floatToHHMM($times_array['remaining']),
            'overtime' => self::floatToHHMM($times_array['overtime'])
        ];
    }

    /**
     * Reset the internal times.
     */
    public function resetTimes()
    {
        $this->resetTimesForArray($this->times);
    }

    /**
     * Reset the times for the given array.
     *
     * @param  array &$arr
     */
    protected function resetTimesForArray(&$arr)
    {
        $arr = self::emptyTimesArray();
    }
}
