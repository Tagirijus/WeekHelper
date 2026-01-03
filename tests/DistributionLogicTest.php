<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/DistributionLogic.php';
require_once __DIR__ . '/../tests/TestTask.php';
require_once __DIR__ . '/../Helper/TasksPlan.php';
require_once __DIR__ . '/../Helper/TimeSlotsDay.php';
require_once __DIR__ . '/../Helper/TimeSpan.php';
require_once __DIR__ . '/../Helper/TimePoint.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\DistributionLogic;
use Kanboard\Plugin\WeekHelper\Helper\TimePoint;
use Kanboard\Plugin\WeekHelper\Helper\TimeSpan;
use Kanboard\Plugin\WeekHelper\tests\TestTask;


final class DistributionLogicTest extends TestCase
{
    public function testDistributionLogicPlanning(): void
    {
        // the task I could get from Kanboard; already sorted
        // by SortingLogic (virtually), by the way.
        // I have a set of tasks for project 1 and 2
        // project 1
        $task_1_a = TestTask::create('1a', 1, 'office', 3, 0.5, 0);
        $task_1_b = TestTask::create('1b', 1, 'office', 3, 1, 0);
        $task_1_c = TestTask::create('1c', 1, 'office', 3, 2, 0);
        // project 2
        $task_2_a = TestTask::create('2a', 2, 'studio', 3, 0.5, 0);
        $task_2_b = TestTask::create('2b', 2, 'studio', 3, 2, 0);
        $task_2_c = TestTask::create('2c', 2, 'studio', 3, 1, 0);
        $init_tasks = [
            $task_1_a,
            $task_1_b,
            $task_1_c,
            $task_2_a,
            $task_2_b,
            $task_2_c,
        ];

        $time_slots_config = [
            'mon' => "6:00-9:00 office\n11:00-15:00",
            'tue' => '',
            'wed' => '',
            'thu' => '',
            'fri' => '',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 0,
        ];

        // expected sorted plan
        $sorted_plan = [
            'mon' => [
                [
                    'task' => $task_1_a,
                    'start' => 360,
                    'end' => 390,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 30,
                ],
                [
                    'task' => $task_1_b,
                    'start' => 390,
                    'end' => 450,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 60,
                ],
                [
                    'task' => $task_1_c,
                    'start' => 450,
                    'end' => 540,
                    'length' => 90,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_2_a,
                    'start' => 660,
                    'end' => 690,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 30,
                ],
                [
                    'task' => $task_2_b,
                    'start' => 690,
                    'end' => 810,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_2_c,
                    'start' => 810,
                    'end' => 840,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'tue' => [],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [
                [
                    'task' => $task_1_c,
                    'start' => 0,
                    'end' => 30,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_2_c,
                    'start' => 30,
                    'end' => 60,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
        ];

        // now the final distribution instance
        $distributor = new DistributionLogic($time_slots_config);
        $distributor->distributeTasks($init_tasks);
        $distributed_plan = $distributor->getTasksPlan()->getPlan();

        $this->assertSame(
            $sorted_plan,
            $distributed_plan,
            'DistributionLogic did not distribute tasks as expected.'
        );
    }

    public function testDepleteUntilTimePoint()
    {
        $time_slots_config = [
            'mon' => "6:00-9:00 office\n11:00-15:00",
            'tue' => '10:00-12:00',
            'wed' => "10:30-15:00\n17:00-18:00",
            'thu' => "10:00-12:00 office\n15:00-16:00 studio",
            'fri' => '10:00-12:00',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 0,
        ];
        $distributor = new DistributionLogic($time_slots_config);
        $distributor->depleteUntilTimePoint(new TimePoint('wed 11:00'));
        $this->assertSame(
            -1,
            $distributor->time_slots_days['mon']->nextSlot(),
            'There should not be any slot left on Monday now.'
        );
        $this->assertSame(
            -1,
            $distributor->time_slots_days['tue']->nextSlot(),
            'There should not be any slot left on Tuesday now.'
        );
        $wed_slot = $distributor->time_slots_days['wed']->nextSlot();
        $this->assertSame(
            0,
            $wed_slot,
            'There should be slots left on Wednesday now.'
        );
        $this->assertSame(
            240,
            $distributor->time_slots_days['wed']->getLengthOfSlot($wed_slot),
            'There should be 4 hours / 240 min left on Wednesday.'
        );
        // some further tests of remaining slots in the week
        $thu_slot = $distributor->time_slots_days['thu']->nextSlot();
        $this->assertSame(
            0,
            $thu_slot,
            'There should be slots left on Thursday.'
        );
        $thu_slot = $distributor->time_slots_days['thu']->nextSlot('studio');
        $this->assertSame(
            1,
            $thu_slot,
            'There should be 1 slot left on Thursday for "studio".'
        );
        $this->assertSame(
            60,
            $distributor->time_slots_days['thu']->getLengthOfSlot($thu_slot),
            'The Thursday studio slot should have 1 hour / 60 min left.'
        );
    }

    public function testDepleteByTimeSpans()
    {
        $time_slots_config = [
            'mon' => "6:00-9:00 office\n11:00-15:00",
            'tue' => '10:00-12:00',
            'wed' => "10:30-15:00\n17:00-18:00",
            'thu' => "10:00-12:00 office\n15:00-16:00 studio",
            'fri' => '10:00-12:00',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 0,
        ];
        $distributor = new DistributionLogic($time_slots_config);

        $time_spans = [
            'mon' => [
                new TimeSpan(300, 360),
                new TimeSpan(360, 600)
            ],
            'tue' => [
                new TimeSpan(540, 660)
            ]
        ];
        $distributor->depleteByTimeSpans($time_spans);
        $this->assertSame(
            1,
            $distributor->time_slots_days['mon']->nextSlot(),
            'There should only be the second slot left on Monday now.'
        );
        $this->assertSame(
            60,
            $distributor->time_slots_days['tue']->getLength(),
            'Tuesday should only have 1 hour / 60 min left.'
        );
    }
}
