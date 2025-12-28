<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


final class TimeHelperTest extends TestCase
{
    public function testSortingLogicConfigParsing()
    {
        $this->assertSame(
            60,
            TimeHelper::hoursToMinutes(1),
            'TimeHelper could not convert hours to minutes correctly.'
        );
        $this->assertSame(
            90,
            TimeHelper::hoursToMinutes(1.5),
            'TimeHelper could not convert hours to minutes correctly.'
        );
        $this->assertSame(
            -30,
            TimeHelper::hoursToMinutes(-0.5),
            'TimeHelper could not convert hours to minutes correctly.'
        );
        $this->assertSame(
            44,
            TimeHelper::hoursToMinutes(0.74),
            'TimeHelper could not convert hours to minutes correctly.'
        );
        $this->assertSame(
            45,
            TimeHelper::hoursToMinutes(0.75),
            'TimeHelper could not convert hours to minutes correctly.'
        );
    }

    public function testReadableToMinutes()
    {
        $this->assertSame(
            390,
            TimeHelper::readableToMinutes("6:30"),
            'TimeHelper could not convert readable 6:30 to 390 minutes.'
        );
    }

    public function testDiffOfWeekDays()
    {
        $this->assertSame(
            1,
            TimeHelper::diffOfWeekDays('mon', 'tue'),
            'TimeHelper could not test weekday string against weekday string.'
        );
        $this->assertSame(
            -2,
            TimeHelper::diffOfWeekDays('Wednesday', 'MON'),
            'TimeHelper could not test weekday string against weekday string.'
        );
        $this->assertSame(
            7,
            TimeHelper::diffOfWeekDays('mon', 'ovr'),
            'TimeHelper could not test weekday string against weekday string.'
        );
        $this->assertSame(
            -6,
            TimeHelper::diffOfWeekDays('Overflow', 'tUesDAY'),
            'TimeHelper could not test weekday string against weekday string.'
        );

        // today testing
        $this->assertSame(
            TimeHelper::diffOfWeekDays('tue', date('D')),
            TimeHelper::diffOfWeekDays('tue', ''),
            'TimeHelper could not test weekday string against weekday string.'
        );
        $this->assertSame(
            TimeHelper::diffOfWeekDays(date('D'), 'wed'),
            TimeHelper::diffOfWeekDays('', 'wed'),
            'TimeHelper could not test weekday string against weekday string.'
        );
    }

    public function testMinutesToReadable()
    {
        $this->assertSame(
            '0:00  min',
            TimeHelper::minutesToReadable(0, '  min'),
            'TimeHelper could not convert minutes to proper readable.'
        );
        $this->assertSame(
            '1:45 h',
            TimeHelper::minutesToReadable(105, ' h'),
            'TimeHelper could not convert minutes to proper readable.'
        );
        $this->assertSame(
            '0:30',
            TimeHelper::minutesToReadable(-30),
            'TimeHelper could not convert minutes to proper readable.'
        );
    }
}
