<?php

/**
 * With this class I want to store the daily quota for
 * a project. It can store the daily limits of minutes
 * which can still be planned.
 *
 * I needed this class to have a more sane workflow
 * with the TasksPlan, which previously handled
 * these limits somehow. This resulted in tasks, for
 * which I worked more on a day, not being planned
 * correctly basically. Worked time in advance was
 * drained from the beginning of the week and not
 * the end.
 *
 * That's why I need such class, which will be able to
 * e.g. update the daily limits according to the already
 * spent time and return updated daily limits for the
 * TasksPlan class accordingly.
 */

namespace Kanboard\Plugin\WeekHelper\Model;


class ProjectQuota
{
    /**
     * Daily minutes quota for the days.
     *
     * @var array
     **/
    var $quota = [
        'mon' => 1440,
        'tue' => 1440,
        'wed' => 1440,
        'thu' => 1440,
        'fri' => 1440,
        'sat' => 1440,
        'sun' => 1440,
    ];

    /**
     * Initialize the ProjetcQuota instance for a specific
     * project.
     *
     * @param array $project_info
     */
    public function __construct($project_info = [])
    {
        $this->initWithProjectInfo($project_info);
    }

    /**
     * Initialize the internal quota with the given project info.
     * Normally I can have a default daily limit for a project.
     * And then there can be limits per day as well. So if there
     * is a default daily limit, this will be used to initialize
     * all days first. After that, if available, specific daily
     * limits for a specific day can be used to override such days.
     *
     * If nothing given, the default daily limit of 1440 minutes
     * will be used - this is a whole day.
     *
     * @param  array $info
     */
    protected function initWithProjectInfo($info = [])
    {
        if (array_key_exists('project_max_hours_day', $info)) {
            $daily_minutes = (int) ($info['project_max_hours_day'] * 60);
            foreach (array_keys($this->quota) as $day) {
                $this->quota[$day] = $daily_minutes;
            }
        }
        foreach (array_keys($this->quota) as $day) {
            if (array_key_exists('project_max_hours_' . $day, $info)) {
                if ($info['project_max_hours_' . $day] != -1) {
                    $daily_minutes = (int) ($info['project_max_hours_' . $day] * 60);
                    $this->quota[$day] = $daily_minutes;
                }
            }
        }
    }

    /**
     * Get the quota for the day.
     *
     * @param  string $day
     * @return integer
     */
    public function getQuota($day)
    {
        return $this->quota[$day] ?? -1;
    }

    /**
     * Substract minutes from the quota for the day.
     * If the quota holds less minutes than to be
     * subtracted, the method will return the amount
     * of minutes, which could not be substracted.
     *
     * If something fails, the method returns -1.
     *
     * @param  string $day
     * @param  integer $minutes
     * @return integer
     */
    public function substractQuota($day, $minutes)
    {
        if (
            array_key_exists($day, $this->quota)
            && is_numeric($minutes)
        ) {
            $diff = $this->quota[$day] - $minutes;
            if ($diff > 0) {
                $this->quota[$day] -= $minutes;
                return 0;
            } else {
                $this->quota[$day] = 0;
                return abs($diff);
            }
        } else {
            return -1;
        }
    }
}