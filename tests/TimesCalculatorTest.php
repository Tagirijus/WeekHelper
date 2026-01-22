<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TimesCalculator.php';
require_once __DIR__ . '/../tests/TestTask.php';



use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TimesCalculator;
use Kanboard\Plugin\WeekHelper\tests\TestTask;


final class TimesCalculatorTest extends TestCase
{
    public function testSimpleTaskTimes()
    {
        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 4
        );
        $tc = new TimesCalculator($task);
        $this->assertSame(
            10.0,
            $tc->getEstimated(),
            'TimesCalculator->getEstimated() return wrong result.'
        );
        $this->assertSame(
            4.0,
            $tc->getSpent(),
            'TimesCalculator->getSpent() return wrong result.'
        );
        $this->assertSame(
            6.0,
            $tc->getRemaining(),
            'TimesCalculator->getRemaining() return wrong result.'
        );
        $this->assertSame(
            0.0,
            $tc->getOvertime(),
            'TimesCalculator->getOvertime() return wrong result.'
        );
        $this->assertFalse(
            $tc->isDone(),
            'TimesCalculator->isDone() should return false.'
        );

        // changing the tasks spent time, like I worked on the tasl
        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 11.5
        );
        $tc = new TimesCalculator($task);
        $this->assertSame(
            1.5,
            $tc->getOvertime(),
            'TimesCalculator->getOvertime() return wrong result.'
        );
        $this->assertTrue(
            $tc->isDone(),
            'TimesCalculator->isDone() should return true.'
        );
    }
}
