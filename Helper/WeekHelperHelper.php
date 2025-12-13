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
     * @var string
     **/
    public $weekpattern = null;

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
            'week_pattern' => $this->getWeekpattern(),
            'time_box_enabled' => $this->configModel->get('weekhelper_time_box_enabled', 1),
            'due_date_week_card_enabled' => $this->configModel->get('weekhelper_due_date_week_card_enabled', 1),
            'full_start_date_enabled' => $this->configModel->get('weekhelper_full_start_date_enabled', 1),
            'due_date_week_list_enabled' => $this->configModel->get('weekhelper_due_date_week_list_enabled', 1),
            'calendarweeks_for_week_difference_enabled' => $this->configModel->get('weekhelper_calendarweeks_for_week_difference_enabled', 0),
            'block_icon_before_task_title' => $this->configModel->get('weekhelper_block_icon_before_task_title', 1),
            'block_ignore_columns' => $this->configModel->get('weekhelper_block_ignore_columns', 'done'),
            'todo_icon_before_task_title' => $this->configModel->get('weekhelper_todo_icon_before_task_title', 1),

            // HoursView
            'non_time_mode_minutes' => $this->configModel->get('hoursview_non_time_mode_minutes', 0),
            'level_1_columns' => $this->configModel->get('hoursview_level_1_columns', ''),
            'level_2_columns' => $this->configModel->get('hoursview_level_2_columns', ''),
            'level_3_columns' => $this->configModel->get('hoursview_level_3_columns', ''),
            'level_4_columns' => $this->configModel->get('hoursview_level_4_columns', ''),
            'level_1_caption' => $this->configModel->get('hoursview_level_1_caption', ''),
            'level_2_caption' => $this->configModel->get('hoursview_level_2_caption', ''),
            'level_3_caption' => $this->configModel->get('hoursview_level_3_caption', ''),
            'level_4_caption' => $this->configModel->get('hoursview_level_4_caption', ''),
            'all_caption' => $this->configModel->get('hoursview_all_caption', ''),
            'ignore_subtask_titles' => $this->configModel->get('hoursview_ignore_subtask_titles', ''),
            'progressbar_enabled' => $this->configModel->get('hoursview_progressbar_enabled', 1),
            'progressbar_opacity' => $this->configModel->get('hoursview_progressbar_opacity', 1),
            'progressbar_0_opacity' => $this->configModel->get('hoursview_progressbar_0_opacity', 0.15),
            'progress_home_project_level' => $this->configModel->get('hoursview_progress_home_project_level', 'all'),
            'hide_0hours_projects_enabled' => $this->configModel->get('hoursview_hide_0hours_projects_enabled', 0),
            'block_hours' => $this->configModel->get('hoursview_block_hours', 0),
            'tooltip_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id'),
            'dashboard_link_level_1' => $this->configModel->get('hoursview_dashboard_link_level_1', 0),
            'dashboard_link_level_2' => $this->configModel->get('hoursview_dashboard_link_level_2', 0),
            'dashboard_link_level_3' => $this->configModel->get('hoursview_dashboard_link_level_3', 0),
            'dashboard_link_level_4' => $this->configModel->get('hoursview_dashboard_link_level_4', 0),
            'dashboard_link_level_all' => $this->configModel->get('hoursview_dashboard_link_level_all', 0),

            // Remaining Box
            'remaining_days_enabled' => $this->configModel->get('weekhelper_remaining_days_enabled', 1),
            'remaining_lvl_days' =>  $this->configModel->get('weekhelper_remaining_lvl_days', ''),
            'remaining_weeks_enabled' => $this->configModel->get('weekhelper_remaining_weeks_enabled', 1),
            'remaining_lvl_weeks' =>  $this->configModel->get('weekhelper_remaining_lvl_weeks', ''),

            // Automatic Planner
            'automatic_planner_sticky_enabled' => $this->configModel->get('weekhelper_automatic_planner_sticky_enabled', 0),
            'level_active_week' =>  $this->configModel->get('weekhelper_level_active_week', ''),
            'level_planned_week' =>  $this->configModel->get('weekhelper_level_planned_week', ''),
            'monday_slots' =>  $this->configModel->get('weekhelper_monday_slots', ''),
            'tuesday_slots' =>  $this->configModel->get('weekhelper_tuesday_slots', ''),
            'wednesday_slots' =>  $this->configModel->get('weekhelper_wednesday_slots', ''),
            'thursday_slots' =>  $this->configModel->get('weekhelper_thursday_slots', ''),
            'friday_slots' =>  $this->configModel->get('weekhelper_friday_slots', ''),
            'saturday_slots' =>  $this->configModel->get('weekhelper_saturday_slots', ''),
            'sunday_slots' =>  $this->configModel->get('weekhelper_sunday_slots', ''),
        ];
    }

    /**
     * Init the cached weekpattern and return it.
     *
     * @return string
     */
    public function getWeekpattern()
    {
        if (is_null($this->weekpattern)) {
            $this->weekpattern = $this->configModel->get('weekhelper_week_pattern', '{YEAR_SHORT}W{WEEK}');
        }
        return $this->weekpattern;
    }

    /**
     * Use the now date to create the week pattern string.
     * Maybe with additional days (for e.g. next or overnext
     * week).
     *
     * Or use the given date via the weekpattern given in
     * the title, if $fromNow is true and a weekpattern will
     * be found in the given title.
     *
     * @param integer $daysAdd
     * @param bool $fromNow
     * @param string $title
     * @return string
     */
    public function createActualStringWithWeekPattern($daysAdd = 0, $fromNow = false, $title = '')
    {
        // fallback: use the actual week (now) to add the days from
        $baseDate = strtotime('+' . $daysAdd . ' days');

        // try to use the week from the given title, if wanted
        if (!$fromNow) {
            $dateFromWeekpatternInTitle = $this->getDateFromWeekPatternString(
                $this->getWeekPatternStringFromTitle($title)
            );
            if ($dateFromWeekpatternInTitle) {
                $baseDate = $dateFromWeekpatternInTitle;
                $baseDate->modify('+' . $daysAdd . ' days');
                $baseDate = $baseDate->getTimestamp();
            }
        }

        $year = date('Y', $baseDate);
        $year_short = substr(date('Y', $baseDate), -2);
        $week = intval(date('W', $baseDate));

        // get the final output string
        return str_replace(
            ['{YEAR}', '{YEAR_SHORT}', '{WEEK}'],
            [$year, $year_short, $week],
            $this->getWeekpattern()
        );
    }

    /**
     * Get the weekpattern from a given task title or similar.
     *
     * @param  string $title
     * @return string
     */
    public function getWeekPatternStringFromTitle($title)
    {
        if (preg_match($this->createRegexFromWeekpattern(), $title, $matches)) {
            return $matches[0];
        } else {
            return '';
        }
    }

    /**
     * Get the start of the week by the given week pattern
     * string. If it fails, just get "now".
     *
     * @param  string $string
     * @return DateTime
     */
    public function getDateFromWeekPatternString($string)
    {
        $out = false;
        preg_match($this->createRegexFromWeekpattern(), $string, $matches);
        if ($matches) {
            $out = new \DateTime();

            // year
            if (isset($matches['YEAR'])) {
                $year = (int) $matches['YEAR'];
            } elseif (isset($matches['YEAR_SHORT'])) {
                $year = (int) $matches['YEAR_SHORT'];
            } else {
                return new \DateTime();
            }

            // week
            if (isset($matches['WEEK'])) {
                $week = (int) $matches['WEEK'];
            } else {
                return new \DateTime();
            }

            $out->setISODate($year, $week);
        }
        return $out;
    }

    /**
     * Convert the given weekpattern to a regex.
     *
     * @return string
     */
    public function createRegexFromWeekpattern()
    {
        $regex = str_replace(
            ['{YEAR}', '{YEAR_SHORT}', '{WEEK}'],
            ['(?P<YEAR>\d{4})', '(?P<YEAR_SHORT>\d{2})', '(?P<WEEK>\d{1,2})'],
            $this->getWeekpattern()
        );
        return '/(' . $regex . ')/';
    }

    /**
     * Prepare the given string with the weekpattern and wrap
     * a span element around the found string.
     *
     * @param  string $title
     * @param  array $task
     * @return string
     */
    public function prepareWeekpatternInTitle($title, $task = [])
    {
        return $this->prepareIconInFrontOfTitle($task) . preg_replace(
            $this->createRegexFromWeekpattern(),
            '<span class="weekhelper-weekpattern-dim">$1</span>',
            $title
        );
    }

    /**
     * Check the config and the task and then prepend some the correct icon
     * (or maybe icons in the future) around the title to show some
     * quick infos.
     *
     * @param  array  $task
     * @return string
     */
    public function prepareIconInFrontOfTitle($task = [])
    {
        // the icon strings, which could be used
        $block_str = '<i class="fa fa-ban" style="color:rgb(200, 0, 0);font-size:1.25em;" title="' . t('Is blocked by other task') . '"></i> ';
        $todo_str = '<i class="fa fa-check-square-o" style="color:rgb(120, 150, 220);font-size:1em;" title="' . t('Has open tasks (in description or comments only)') . '"></i> ';

        // the logic, which icon(s) should be used finally
        $task_should_show_blocked = $this->showBlockedIcon($task);
        $task_should_show_todo = $this->showTodoIcon($task);

        // blocked status has priority over todos
        if ($task_should_show_blocked) {
            $icon_str = $block_str;
        // otherwise the todo-icon can be shown, if enabled
        } elseif ($task_should_show_todo) {
            $icon_str = $todo_str;
        // fallback is no icon, after all
        } else {
            $icon_str = '';
        }


        return $icon_str;
    }

    /**
     * The logic with which a task is interpreted as "blocked" for the
     * icon prepending in the title, depending on the configuration
     * and also the logic, if other tasks do block it after all.
     *
     * @param  array  $task
     * @return boolean
     */
    public function showBlockedIcon($task = [])
    {
        $out = false;
        if ($this->configModel->get('weekhelper_block_icon_before_task_title', 1) == 1) {
            $all_links = $this->taskLinkModel->getAllGroupedByLabel($task['id']);
            foreach ($all_links as $label => $link) {
                if ($label == 'is blocked by') {
                    foreach ($link as $task) {
                        $columns = $this->configModel->get('weekhelper_block_ignore_columns', 'done');
                        $columns = explode(',', $columns);
                        if ($task['is_active'] == 1 && !in_array($task['column_title'], $columns)) {
                            $out = true;
                            break;
                        }
                    }
                }
            }
        }
        return $out;
    }

    /**
     * The logic with which a task is interpreted as "open tasks" for the
     * icon prepending in the title, depending on the configuration
     * and also the logic, if it has open tasks in the description or the
     * comments.
     *
     * @param  array  $task
     * @return boolean
     */
    public function showTodoIcon($task = [])
    {
        $out = false;
        if ($this->configModel->get('weekhelper_todo_icon_before_task_title', 1) == 1) {
            $task_has_open_task_in_description = $this->stringHasOpenTask($task['description']);
            $task_has_open_task_in_comments = $this->tasksCommentsHaveOpenTask($task);
            $out = $task_has_open_task_in_description || $task_has_open_task_in_comments;
        }
        return $out;
    }

    /**
     * Go through the tasks comments and check if they contain open tasks.
     *
     * @param  array  $task
     * @return boolean
     */
    public function tasksCommentsHaveOpenTask($task = [])
    {
        $out = false;
        $comments = $this->commentModel->getAll($task['id']);
        foreach ($comments as $comment) {
            if ($this->stringHasOpenTask($comment['comment'])) {
                $out = true;
                break;
            }
        }
        return $out;
    }

    /**
     * Checks if the given string contains the logic for an open task.
     * Initially it is the string "- [ ]", but maybe I could change it
     * over time.
     *
     * @param  string $string
     * @return boolean
     */
    public function stringHasOpenTask($string = '')
    {
        return strpos($string, "- [ ]") !== false;
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

        // Calculate the difference in weeks
        $interval = $datetime1->diff($datetime2);
        $weeksDifference = $interval->days / 7;

        // Check if the target date is in the past
        if ($datetime2 < $datetime1) {
            $weeksDifference = -$weeksDifference;
        }

        return round($weeksDifference);
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
     * the CSS string from the (parsed) config array.
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

    /**
     * Add one week to the given title according to the
     * week pattern of the plugin.
     *
     * The $fromNow parameter is for either using the actual
     * week automatically or use the week, given in the title
     * with the weekpattern format, set up in the config.
     *
     * @param string $title
     * @param bool $fromNow
     * @return string
     */
    public function addOneWeekToGivenTitle($title, $fromNow = false)
    {
        $regex = $this->createRegexFromWeekpattern();
        $nextWeek = $this->createActualStringWithWeekPattern(7, $fromNow, $title);

        // replace the previous week with the next week
        return preg_replace($regex, $nextWeek, $title);
    }
}
