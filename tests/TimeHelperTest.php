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
}
