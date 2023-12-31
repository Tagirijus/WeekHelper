<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;


class WeekHelperHelper extends Base
{

    /**
     * @var boolean
     **/
    public $remainingDaysEnabled = null;

    /**
     * @var boolean
     **/
    public $remainingWeeksEnabled = null;

    /**
     * @var array
     **/
    public $remainingDaysLvl = null;

    /**
     * @var array
     **/
    public $remainingWeeksLvl = null;

    /**
     * Get configuration for plugin as array.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'title' => t('WeekHelper') . ' &gt; ' . t('Settings'),

            // Weeks
            'headerdate_enabled' => $this->configModel->get('weekhelper_headerdate_enabled', 1),
            'week_pattern' => $this->configModel->get('weekhelper_week_pattern', '{YEAR_SHORT}W{WEEK}'),
            'time_box_enabled' => $this->configModel->get('weekhelper_time_box_enabled', 1),
            'due_date_week_card_enabled' => $this->configModel->get('weekhelper_due_date_week_card_enabled', 1),
            'full_start_date_enabled' => $this->configModel->get('weekhelper_full_start_date_enabled', 1),
            'due_date_week_list_enabled' => $this->configModel->get('weekhelper_due_date_week_list_enabled', 1),
            'calendarweeks_for_week_difference_enabled' => $this->configModel->get('weekhelper_calendarweeks_for_week_difference_enabled', 0),

            // HoursView
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

            // Remaining Box
            'remaining_days_enabled' => $this->configModel->get('weekhelper_remaining_days_enabled', 1),
            'remaining_lvl_days' =>  $this->configModel->get('weekhelper_remaining_lvl_days', ''),
            'remaining_weeks_enabled' => $this->configModel->get('weekhelper_remaining_weeks_enabled', 1),
            'remaining_lvl_weeks' =>  $this->configModel->get('weekhelper_remaining_lvl_weeks', ''),
        ];
    }

    /**
     * Use the now date to create the week pattern string.
     * Maybe with additional days (for e.g. next or overnext
     * week).
     *
     * @param integer $daysAdd
     * @return string
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
     * Info: this method will ignore the day times and
     *       just use the plain dates to calculate the
     *       days!
     *
     * @param  integer $unix
     * @return integer
     */
    public function getRemainingDaysFromNowTillTimestamp($unix = 0)
    {
        $datetime1 = new \DateTime(); // start time
        $datetime1->setTime(0, 0, 0, 0);
        $datetime2 = new \DateTime(); // end time
        $datetime2->setTime(0, 0, 0, 0);
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
     * @return integer
     */
    public function getRemainingWeeksFromNowTillTimestamp($unix = 0)
    {
        // new optional calculation method
        if ($this->configModel->get('weekhelper_calendarweeks_for_week_difference_enabled', 0) == 1) {
            return $this->getRemainingWeeksFromNowTillTimestampWithCalendarWeeks($unix);

        // old default calculation method
        } else {
            return $this->getRemainingWeeksFromNowTillTimestampWithPlainDays($unix);
        }
    }

    /**
     * This method calculates the week difference from
     * now to the given unix timestamp. It simply uses
     * the logic that one week is equal to seven days.
     *
     * It is the default and was the only method
     * till v2.2.
     *
     * @param  integer $unix
     * @return integer
     */
    protected function getRemainingWeeksFromNowTillTimestampWithPlainDays($unix = 0)
    {
        $days = $this->getRemainingDaysFromNowTillTimestamp($unix);
        $weeks = $days / 7;
        $weeks = round($weeks);
        return $weeks;
    }

    /**
     * This method calculates the week difference from
     * now to the given unix timestamp. This method uses
     * the 52 calendar weeks of the year for the calculation.
     *
     * @param  integer $unix
     * @return integer
     */
    protected function getRemainingWeeksFromNowTillTimestampWithCalendarWeeks($unix = 0)
    {
        // now
        $datetime1 = new \DateTime();
        $datetime1->setTime(0, 0, 0, 0);

        // target date
        $datetime2 = new \DateTime();
        $datetime2->setTime(0, 0, 0, 0);
        $datetime2->setTimestamp($unix);

        // year difference of both
        $year_diff = abs($datetime2->format('Y') - $datetime1->format('Y'));

        // get calendar weeks
        $week1 = $datetime1->format('W');
        $week2 = $datetime2->format('W') + $year_diff * 52;

        return $week2 - $week1;
    }

    /**
     * Get config for remaining box feature
     * and cache it in this classes variables.
     */
    public function initRemainingBoxConfig(): void
    {
        $this->remainingDaysEnabled = $this->configModel->get('weekhelper_remaining_days_enabled', 1);
        $this->remainingDaysLvl = $this->configModel->get('weekhelper_remaining_lvl_days', '');
        $this->remainingDaysLvl = $this->parseRemainingLevels($this->remainingDaysLvl);
        $this->remainingWeeksEnabled = $this->configModel->get('weekhelper_remaining_weeks_enabled', 1);
        $this->remainingWeeksLvl = $this->configModel->get('weekhelper_remaining_lvl_weeks', '');
        $this->remainingWeeksLvl = $this->parseRemainingLevels($this->remainingWeeksLvl);
    }

    /**
     * Parse the given remaining box feature config
     * string and generate an array from it.
     *
     * @param  string $configStr
     * @return array
     */
    public function parseRemainingLevels($configStr)
    {
        $out = [];
        $config_per_line = preg_split("/\r\n|\n|\r/", $configStr);
        foreach ($config_per_line as $config_line) {
            $config_tmp = explode('=', $config_line);
            if (count($config_tmp) > 1) {
                $out[] = [
                    'difference' => $config_tmp[0],
                    'css' => $config_tmp[1]
                ];
            }
        }
        return $out;
    }

    /**
     * Show remaining days? Get it from config
     * and return the bool directly.
     *
     * @return boolean
     */
    public function showRemainingDays()
    {
        if (is_null($this->remainingDaysEnabled)) {
            $this->initRemainingBoxConfig();
        }
        return $this->remainingDaysEnabled;
    }

    /**
     * Show remaining weeks? Get it from config
     * and return the bool directly.
     *
     * @return boolean
     */
    public function showRemainingWeeks()
    {
        if (is_null($this->remainingWeeksEnabled)) {
            $this->initRemainingBoxConfig();
        }
        return $this->remainingWeeksEnabled;
    }

    /**
     * This method will get a timestamp and check against
     * todays date and calculate the difference and then
     * output th according CSS set in the config for this
     * difference.
     *
     * IN DAYS for this method.
     *
     * @param  integer $unix
     * @return string
     */
    public function getCSSForRemainingDaysTimestamp($unix = 0)
    {
        return $this->getCSSForRemainingTimestamp($unix, 'days');
    }

    /**
     * This method will get a timestamp and check against
     * todays date and calculate the difference and then
     * output th according CSS set in the config for this
     * difference.
     *
     * IN WEEKS for this method.
     *
     * @param  integer $unix
     * @return string
     */
    public function getCSSForRemainingWeeksTimestamp($unix = 0)
    {
        return $this->getCSSForRemainingTimestamp($unix, 'weeks');
    }

    /**
     * Wrapper method for the respective methods:
     *
     * This method will get a timestamp and check against
     * todays date and calculate the difference and then
     * output th according CSS set in the config for this
     * difference.
     *
     * @param  integer $unix
     * @param  string  $what
     * @return string
     */
    protected function getCSSForRemainingTimestamp($unix = 0, $what = 'days')
    {
        if (is_null($this->remainingDaysLvl) || is_null($this->remainingWeeksLvl)) {
            $this->initRemainingBoxConfig();
        }

        // first get the difference and the config array to use
        if ($what == 'days') {
            $difference = $this->getRemainingDaysFromNowTillTimestamp($unix);
            $levels = $this->remainingDaysLvl;
        } elseif ($what == 'weeks') {
            $difference = $this->getRemainingWeeksFromNowTillTimestamp($unix);
            $levels = $this->remainingWeeksLvl;
        } else {
            $difference = 0;
            $levels = [];
        }

        // now check and choose CSS string to output
        return $this->getCSSFromDifference($difference, $levels);
    }

    /**
     * Get the CSS string with the given difference.
     * This basically is the logic on how to choose
     * the CS string from the (parsed) config array.
     *
     * @param  integer $difference
     * @param  array $levels
     * @return string
     */
    protected function getCSSFromDifference($difference = 0, $levels = [])
    {
        $out = '';
        $lowest = 99999;
        foreach ($levels as $level) {
            if ($difference <= $level['difference'] && $level['difference'] <= $lowest) {
                $out = $level['css'];
                $lowest = $level['difference'];
            }
        }
        return $out;
    }

    /**
     * Show Week of due date on card, if enabled in the config.
     *
     * @param  integer $dueDate
     * @return string
     */
    public function showWeekOfDueDateOnCard($dueDate = 0)
    {
        $out = '';
        if ($this->configModel->get('weekhelper_due_date_week_card_enabled', 1)) {
            $date = new \DateTime();
            $date->setTimestamp($dueDate);
            $week = $date->format("W");
            $out = '<i>(W' . $week . ')</i>';
        }
        return $out;
    }

    /**
     * Show Week of due date in list, if enabled in the config.
     *
     * @param  integer $dueDate
     * @return string
     */
    public function showWeekOfDueDateInList($dueDate = 0)
    {
        $out = '';
        if ($this->configModel->get('weekhelper_due_date_week_list_enabled', 1)) {
            $date = new \DateTime();
            $date->setTimestamp($dueDate);
            $week = $date->format("W");
            $out = '<i>(W' . $week . ')</i>&nbsp;';
        }
        return $out;
    }

    /**
     * Get the config in the template, if the full started
     * date should be shown on the card.
     *
     * @return boolean
     */
    public function showFullStartedDateOnCard()
    {
        return $this->configModel->get('weekhelper_full_start_date_enabled', 1) == 1;
    }
}
