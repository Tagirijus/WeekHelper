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
            'title' => t('WeekHelper') . ' &gt; ' . t('Settings'),
            'headerdate_enabled' => $this->configModel->get('weekhelper_headerdate_enabled', 1),
            'week_pattern' => $this->configModel->get('weekhelper_week_pattern', 'Y{YEAR_SHORT}-W{WEEK}'),
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
        $weekpattern = $this->configModel->get('weekhelper_week_pattern', 'Y{YEAR_SHORT}-W{WEEK}');

        // get the final output string
        return str_replace(
            ['{YEAR}', '{YEAR_SHORT}', '{WEEK}'],
            [$year, $year_short, $week],
            $weekpattern
        );
    }
}
