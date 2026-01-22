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
    }

    public function testSubtasksCalculations()
    {
        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 4
        );
        $subtasks = [
            TestTask::createSub(
                status: 1,
                time_estimated: 2,
                time_spent: 0.5,
            ),
            TestTask::createSub(
                status: 1,
                time_estimated: 6,
                time_spent: 2,
            ),
        ];
        $tc = new TimesCalculator($task, $subtasks);

        // initial times of the parent task should be "overwritten" with
        // the subtasks times here.
        //
        // yet: normally in Kanboard the tasks times will get overwritten
        // on adding, editing or deleting subtasks already anyway. still
        // I coded that subtasks times will be used in the calculations,
        // just in case.
        $this->assertSame(
            8.0,
            $tc->getEstimated(),
            'TimesCalculator->getEstimated() return wrong result with subtasks test..'
        );
        $this->assertSame(
            2.5,
            $tc->getSpent(),
            'TimesCalculator->getSpent() return wrong result with subtasks test..'
        );
        $this->assertSame(
            5.5,
            $tc->getRemaining(),
            'TimesCalculator->getRemaining() return wrong result with subtasks test..'
        );
        $this->assertSame(
            0.0,
            $tc->getOvertime(),
            'TimesCalculator->getOvertime() return wrong result with subtasks test..'
        );
    }

    public function testDoneStatus()
    {
        // ---
        // FOR A SIMPLE TASK
        // ---

        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 4
        );
        $tc = new TimesCalculator($task);
        $this->assertFalse(
            $tc->isDone(),
            'TimesCalculator->isDone() should be false in status tests.'
        );

        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 11
        );
        $tc = new TimesCalculator($task);
        $this->assertTrue(
            $tc->isDone(),
            'TimesCalculator->isDone() should be true in status tests.'
        );

        // ---
        // FOR SUBTASKS CALCULATIONS
        // ---
        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 4
        );
        $subtasks = [
            TestTask::createSub(
                status: 1,
                time_estimated: 2,
                time_spent: 0.5,
            ),
        ];
        $tc = new TimesCalculator($task, $subtasks);
        $this->assertFalse(
            $tc->isDone(),
            'TimesCalculator->isDone() should be false in status tests with subtasks.'
        );

        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 4
        );
        $subtasks = [
            TestTask::createSub(
                status: 2,
                time_estimated: 2,
                time_spent: 0.5,
            ),
            TestTask::createSub(
                status: 1,
                time_estimated: 1,
                time_spent: 1,
            ),
        ];
        $tc = new TimesCalculator($task, $subtasks);
        $this->assertFalse(
            $tc->isDone(),
            'TimesCalculator->isDone() should be false in status tests with subtasks.'
        );

        $task = TestTask::create(
            time_estimated: 10,
            time_spent: 4
        );
        $subtasks = [
            TestTask::createSub(
                status: 2,
                time_estimated: 2,
                time_spent: 0.5,
            ),
        ];
        $tc = new TimesCalculator($task, $subtasks);
        $this->assertTrue(
            $tc->isDone(),
            'TimesCalculator->isDone() should be true in status tests with subtasks.'
        );
    }

    public function testNonTimeMode()
    {
        $task = TestTask::create(score: 6);
        $subtasks = [
            TestTask::createSub(status: 2),
            TestTask::createSub(status: 1),
        ];
        $tc = new TimesCalculator($task, $subtasks, ['non_time_mode_minutes' => 10]);

        $this->assertSame(
            1.0,
            $tc->getEstimated(),
            'TimesCalculator->getEstimated() in non time mode output is wrong.'
        );
        $this->assertSame(
            0.75,
            $tc->getSpent(),
            'TimesCalculator->getSpent() in non time mode output is wrong.'
        );
        $this->assertSame(
            0.25,
            $tc->getRemaining(),
            'TimesCalculator->getRemaining() in non time mode output is wrong.'
        );
        $this->assertSame(
            0.0,
            $tc->getOvertime(),
            'TimesCalculator->getOvertime() in non time mode output is wrong.'
        );
    }

    public function testOvertime()
    {
        $task = TestTask::create();
        $subtasks = [
            TestTask::createSub(
                status: 1,
                time_estimated: 2,
                time_spent: 3,
            ),
        ];
        $tc = new TimesCalculator($task, $subtasks);
        $this->assertSame(
            1.0,
            $tc->getOvertime(),
            'TimesCalculator->getOvertime() is wrong.'
        );
    }
}
