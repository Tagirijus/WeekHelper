<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TimeSpan.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimeSpan;


final class TimeSpanTest extends TestCase
{
    public function testTimeSpanInitialize(): void
    {
        $time_span = new TimeSpan(10, 100);

        $this->assertIsInt($time_span->getStart());
        $this->assertIsInt($time_span->getEnd());
    }

    public function testTimeSpanSetterGetter(): void
    {
        $time_span = new TimeSpan(10, 100);

        $this->assertSame(
            10,
            $time_span->getStart(),
            'TimeSpan start value is not the initilaized value.'
        );
        $this->assertSame(
            100,
            $time_span->getEnd(),
            'TimeSpan end value is not the initilaized value.'
        );
        $this->assertSame(
            90,
            $time_span->length(),
            'TimeSpan length value is not correct.'
        );
    }

    public function testTimeSpanChecks()
    {
        $time_span = new TimeSpan(10, 100);

        $this->assertTrue(
            $time_span->isIn(99),
            'TimeSpan->isIn(99) is not between start of 10 and end of 100.'
        );
        $this->assertSame(
            89,
            $time_span->diffToStart(99),
            'TimeSpan->diffToStart(99) is not 89 from start of 10.'
        );
        $this->assertSame(
            -1,
            $time_span->diffToEnd(99),
            'TimeSpan->diffToEnd(99) is not 1 from end of 100.'
        );
    }

    public function testTimeSpanMethods()
    {
        $time_span = new TimeSpan(10, 100);

        $time_span->deplete();
        $this->assertSame(
            0,
            $time_span->length(),
            'TimeSpan->deplete() did not set start to end, probably.'
        );
    }
}
