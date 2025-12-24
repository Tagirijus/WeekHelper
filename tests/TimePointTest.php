<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TimePoint.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimePoint;


final class TimePointTest extends TestCase
{
    public function testInitialization()
    {
        // should be the automatic set date of "now"
        $timepoint_a = new TimePoint();
        // here I am creating "now" manually for the test
        $now = date('D G:i');
        $timepoint_b = new TimePoint($now);
        // they now should be the same
        $this->assertTrue(
            $timepoint_a->isSame($timepoint_b),
            'Timepoint A and B are not the same ...'
        );

        // now for some checkings of internal settings
        $timepoint = new TimePoint('Thu 4:23');
        $this->assertSame(
            'thu',
            $timepoint->getDay(),
            'Given TimePoint should be on Thursday'
        );
        $this->assertSame(
            263,
            $timepoint->getTime(),
            'Given TimePoint should be at 4:23 / 263 min.'
        );
    }
}
