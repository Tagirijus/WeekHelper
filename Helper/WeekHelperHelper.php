<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;


class WeekHelperHelper extends Base
{

    /**
     * Get configuration for plugin as array.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            // WeekHelper
            'title' => t('WeekHelper') . ' &gt; ' . t('Settings'),
            'headerdate_enabled' => $this->configModel->get('weekhelper_headerdate_enabled', 1),
            'week_pattern' => $this->configModel->get('weekhelper_week_pattern', '{YEAR_SHORT}W{WEEK}'),
            'time_box_enabled' => $this->configModel->get('weekhelper_time_box_enabled', 1),

            // HoursView
            'title' => t('HoursView') . ' &gt; ' . t('Settings'),
            'level_1_columns' => $this->configModel->get('hoursview_level_1_columns', ''),
            'level_2_columns' => $this->configModel->get('hoursview_level_2_columns', ''),
            'level_3_columns' => $this->configModel->get('hoursview_level_3_columns', ''),
            'level_4_columns' => $this->configModel->get('hoursview_level_4_columns', ''),
            'level_1_caption' => $this->configModel->get('hoursview_level_1_caption', ''),
            'level_2_caption' => $this->configModel->get('hoursview_level_2_caption', ''),
            'level_3_caption' => $this->configModel->get('hoursview_level_3_caption', ''),
            'level_4_caption' => $this->configModel->get('hoursview_level_4_caption', ''),
            'all_caption' => $this->configModel->get('hoursview_all_caption', ''),
            'progressbar_enabled' => $this->configModel->get('hoursview_progressbar_enabled', 1),
            'progressbar_opacity' => $this->configModel->get('hoursview_progressbar_opacity', 1),
            'progressbar_0_opacity' => $this->configModel->get('hoursview_progressbar_0_opacity', 0.15),
            'progress_home_project_level' => $this->configModel->get('hoursview_progress_home_project_level', 'all'),
        ];
    }

    /**
     * Use the now date to create the week pattern string.
     * Maybe with additional days (for e.g. next or overnext
     * week).
     *
     * @param integer $daysAdd
     * @return [type] [description]
     */
    public function createActualStringWithWeekPattern($daysAdd = 0)
    {
        // get times
        $adder = strtotime('+' . $daysAdd . ' days');
        $year = date('Y', $adder);
        $year_short = substr(date('Y', $adder), -2);
        $week = date('W', $adder);

        // get other base strings
        $weekpattern = $this->configModel->get('weekhelper_week_pattern', '{YEAR_SHORT}W{WEEK}');

        // get the final output string
        return str_replace(
            ['{YEAR}', '{YEAR_SHORT}', '{WEEK}'],
            [$year, $year_short, $week],
            $weekpattern
        );
    }

    /**
     * Convert the given weekpattern to a regex.
     *
     * @param  string $weekpattern
     * @return string
     */
    public function createRegexFromWeekpattern($weekpattern)
    {
        $regex = str_replace(
            ['{YEAR}', '{YEAR_SHORT}', '{WEEK}'],
            ['\d{4}', '\d{2}', '\d{1,2}'],
            $weekpattern
        );
        return '/(' . $regex . ')/';
    }

    /**
     * Prepare the given string with the weekpattern and wrap
     * a span element around the found string.
     *
     * @param  string $title
     * @return string
     */
    public function prepareWeekpatternInTitle($title)
    {
        $weekpattern = $this->configModel->get('weekhelper_week_pattern', '{YEAR_SHORT}W{WEEK}');
        return preg_replace(
            $this->createRegexFromWeekpattern($weekpattern),
            '<span class="weekhelper-weekpattern-dim">$1</span>',
            $title
        );
    }

    /**
     * Return the remaining days as an integer from
     * now to the given unix timestamp.
     *
     * @param  integer $unix
     * @return integer
     */
    public function getRemainingDaysFromTimestamp($unix = 0)
    {
        $datetime1 = new \DateTime(); // start time
        $datetime2 = new \DateTime(); // end time
        $datetime2->setTimestamp($unix);
        $interval = $datetime1->diff($datetime2);
        if ($interval->invert) {
            return '-' . $interval->days;
        } else {
            return $interval->days;
        }
    }

    /**
     * Return the remaining weeks as an integer from
     * now to the given unix timestamp.
     *
     * @param  integer $unix
     * @param  boolean $ceil
     * @return integer
     */
    public function getRemainingWeeksFromTimestamp($unix = 0, $ceil = true)
    {
        $days = $this->getRemainingDaysFromTimestamp($unix);
        $weeks = $days / 7;
        if ($ceil) {
            $weeks = ceil($weeks);
        }
        return $weeks;
    }
}
