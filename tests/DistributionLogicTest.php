<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/DistributionLogic.php';
require_once __DIR__ . '/../tests/TestTask.php';
require_once __DIR__ . '/../Model/TasksPlan.php';
require_once __DIR__ . '/../Model/TimesCalculator.php';
require_once __DIR__ . '/../Model/TimeSlotsDay.php';
require_once __DIR__ . '/../Model/TimeSpan.php';
require_once __DIR__ . '/../Model/TimePoint.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\DistributionLogic;
use Kanboard\Plugin\WeekHelper\Model\TimePoint;
use Kanboard\Plugin\WeekHelper\Model\TimeSpan;
use Kanboard\Plugin\WeekHelper\tests\TestTask;


final class DistributionLogicTest extends TestCase
{
    public function testDistributionLogicPlanning(): void
    {
        // the task I could get from Kanboard; already sorted
        // by SortingLogic (virtually), by the way.
        // I have a set of tasks for project 1 and 2
        // project 1
        $task_1_a = TestTask::create(
            project_id: 1,
            project_max_hours_day: 3,
            project_type: 'office',
            time_remaining: 0.5,
            title: '1a',
        );
        $task_1_b = TestTask::create(
            project_id: 1,
            project_max_hours_day: 3,
            project_type: 'office',
            time_remaining: 1,
            title: '1b',
        );
        $task_1_c = TestTask::create(
            project_id: 1,
            project_max_hours_day: 3,
            project_type: 'office',
            time_remaining: 2,
            title: '1c',
        );
        // project 2
        $task_2_a = TestTask::create(
            project_id: 2,
            project_max_hours_day: 3,
            project_type: 'studio',
            time_remaining: 0.5,
            title: '2a',
        );
        $task_2_b = TestTask::create(
            project_id: 2,
            project_max_hours_day: 3,
            project_type: 'studio',
            time_remaining: 2,
            title: '2b',
        );
        $task_2_c = TestTask::create(
            project_id: 2,
            project_max_hours_day: 3,
            project_type: 'studio',
            time_remaining: 1,
            title: '2c',
        );
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
            'non_time_mode_minutes' => 0,
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
            'non_time_mode_minutes' => 0,
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
            'non_time_mode_minutes' => 0,
        ];
        $distributor = new DistributionLogic($time_slots_config);

        $time_spans = [
            'mon' => [
                // 5:00-6:00
                ['timespan' => new TimeSpan(300, 360), 'title' => ''],
                // 6:00-10:00
                ['timespan' => new TimeSpan(360, 600), 'title' => '']
            ],
            'tue' => [
                // 9:00-11:00
                ['timespan' => new TimeSpan(540, 660), 'title' => '']
            ],
            'wed' => [
                // 10:30-15:00
                ['timespan' => new TimeSpan(630, 900), 'title' => ''],
                // 17:30 - 19:00
                ['timespan' => new TimeSpan(1050, 1140), 'title' => '']
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
        $this->assertSame(
            1,
            $distributor->time_slots_days['wed']->nextSlot(),
            'Wednesday should only have the second slot left.'
        );
        $this->assertSame(
            1020,
            $distributor->time_slots_days['wed']->getStartOfSlot(1),
            'Wednesday second slot should start at 17:00 / 1020 minutes.'
        );
        $this->assertSame(
            30,
            $distributor->time_slots_days['wed']->getLengthOfSlot(1),
            'Wednesday second slot should have 0.5 hours / 30 minutes left.'
        );
    }

    public function testDepleteByTimeSpansB()
    {
        $time_slots_config = [
            'mon' => "6:00-8:30\n11:00-13:00",
            'tue' => '5:45-10:00',
            'wed' => '10:15-11:20',
            'thu' => '',
            'fri' => '',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 0,
            'non_time_mode_minutes' => 0,
        ];
        $distributor = new DistributionLogic($time_slots_config);

        $time_spans = [
            'mon' => [
                // 7:45-17:00
                ['timespan' => new TimeSpan(465, 1020), 'title' => '']
            ],
            'tue' => [
                // 5:45-9:00
                ['timespan' => new TimeSpan(345, 540), 'title' => '']
            ],
            'wed' => [
                // 10:15-11:20
                ['timespan' => new TimeSpan(615, 680), 'title' => '']
            ],
        ];
        $distributor->depleteByTimeSpans($time_spans);

        // from Monday the second TimeSlot of 11:00-13:00 should be
        // depleted completely.
        // the first TimeSlot of 6:00-8:30 should be depleted
        // between 7:45-8:30; thus leaving only
        // 6:00-7:45 available; 1:45 hours of length
        // (360-465)            (105 minutes)
        $this->assertSame(
            0,
            $distributor->time_slots_days['mon']->nextSlot(),
            'Only the first slot of Monday should be available.'
        );
        $this->assertSame(
            105,
            $distributor->time_slots_days['mon']->getLength(),
            'Monday should have the length of 1:45 hours / 105 min.'
        );
        $this->assertSame(
            360,
            $distributor->time_slots_days['mon']->getStartOfSlot(0),
            'Monday should start at 6:00 / 360 min.'
        );
        $this->assertSame(
            465,
            $distributor->time_slots_days['mon']->getEndOfSlot(0),
            'Monday should end at 7:45 / 465 min.'
        );

        // for Tuesday the first timeslot should be depleted until 9:00,
        // leaving 1 hour / 60 min between 9:00-10:00 (540-600)
        $this->assertSame(
            0,
            $distributor->time_slots_days['tue']->nextSlot(),
            'Only the first slot of Tuesday should be available.'
        );
        $this->assertSame(
            60,
            $distributor->time_slots_days['tue']->getLength(),
            'Tuesday should have the length of 1 hours / 60 min.'
        );
        $this->assertSame(
            540,
            $distributor->time_slots_days['tue']->getStartOfSlot(0),
            'Tuesday should start at 9:00 / 540 min.'
        );
        $this->assertSame(
            600,
            $distributor->time_slots_days['tue']->getEndOfSlot(0),
            'Tuesday should end at 10:00 / 600 min.'
        );

        // for Wednesday everything should be depleted
        $this->assertSame(
            -1,
            $distributor->time_slots_days['wed']->nextSlot(),
            'No slot of Wednesday should be available.'
        );
        $this->assertSame(
            0,
            $distributor->time_slots_days['wed']->getLength(),
            'Wednesday should have the length of 0 hours / 0 min.'
        );
        $this->assertSame(
            680,
            $distributor->time_slots_days['wed']->getStartOfSlot(0),
            'Wednesday should start at its end of 11:20 / 680 min.'
        );
        $this->assertSame(
            680,
            $distributor->time_slots_days['wed']->getEndOfSlot(0),
            'Wednesday should end at its end of 11:20 / 680 min.'
        );
    }

    public function testBlockingConfigParser()
    {
        $distributor = new DistributionLogic();

        $blocking_config = "mon 5:00-6:00\nmon 6:00-10:00 monday 2\ntue 9:00-11:00 tuesday";

        // unfortunately I cannot compare the array, since the TimeSpan instances
        // are different. So I have to compare their components.
        [$blocking_timespans, $pseudo_tasks] = $distributor::blockingConfigParser($blocking_config);

        $this->assertSame(
            300,
            $blocking_timespans['mon'][0]['timespan']->getStart(),
            'Parsed blocking config failed.'
        );
        $this->assertSame(
            'monday 2',
            $blocking_timespans['mon'][1]['title'],
            'Parsed blocking config failed.'
        );
        $this->assertSame(
            120,
            $blocking_timespans['tue'][0]['timespan']->length(),
            'Parsed blocking config failed.'
        );

        $this->assertSame(
            300,
            $pseudo_tasks['mon'][0]['start'],
            'Parsed blocking config failed.'
        );
        $this->assertSame(
            'monday 2',
            $pseudo_tasks['mon'][1]['task']['title'],
            'Parsed blocking config failed.'
        );
        $this->assertSame(
            120,
            $pseudo_tasks['tue'][0]['length'],
            'Parsed blocking config failed.'
        );
    }

    public function testBlockingConfigParserWithTimePoint()
    {
        $distributor = new DistributionLogic();

        $blocking_config = "mon 5:00-6:00\nmon 6:00-10:00 monday 2\ntue 9:00-11:00 tuesday";

        [
            $blocking_timespans,
            $pseudo_tasks
        ] = $distributor::blockingConfigParser(
            $blocking_config,
            new TimePoint('mon 7:00')
        );

        // the TimePoint should have "removed" the first blocking TimeSpan,
        // since it's in the past (ending of 6:00 is before 7:00). So the
        // first slot to check is the second, which now has the key of 0!
        $this->assertSame(
            420,
            $pseudo_tasks['mon'][0]['start'],
            'Parsed blocking config with TimePoint failed.'
        );
        $this->assertSame(
            180,
            $pseudo_tasks['mon'][0]['length'],
            'Parsed blocking config with TimePoint failed.'
        );
        $this->assertSame(
            60,
            $pseudo_tasks['mon'][0]['spent'],
            'Parsed blocking config with TimePoint failed.'
        );

        // Tuesday should have still the full length and is not
        // modified by the TimePoint at all.
        $this->assertSame(
            120,
            $pseudo_tasks['tue'][0]['length'],
            'Parsed blocking config with TimePoint failed.'
        );
    }

    public function testBlockingConfigParserWithTimePointB()
    {
        // I had a bug that I created a pseudo blocking task from 9:00-17:00.
        // Then it was 7:45 and normally another task should still be planned,
        // until that point, but somehow this blocking task got shown from
        // 7:45-17:00 ...
        // With this test I try to solve this issue

        $distributor = new DistributionLogic();

        $blocking_config = "mon 9:00-17:00";

        [
            $blocking_timespans,
            $pseudo_tasks
        ] = $distributor::blockingConfigParser(
            $blocking_config,
            new TimePoint('mon 7:45')
        );

        // the start should still be 9:00 and not 7:45
        $this->assertSame(
            540,
            $blocking_timespans['mon'][0]['timespan']->getStart(),
            'Start of blocking pseudo task should not be "now", if "now" is before its original start.'
        );

    }

    public function testMinSlotLength(): void
    {
        $task_a = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            project_type: 'office',
            time_remaining: 0.5,
            title: '1a',
        );
        $task_b = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            project_type: 'office',
            time_remaining: 1,
            title: '1b',
        );
        $task_c = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            project_type: 'office',
            time_remaining: 2,
            title: '1c',
        );
        $init_tasks = [
            $task_a,
            $task_b,
            $task_c,
        ];

        $time_slots_config = [
            'mon' => "6:00-6:20\n11:00-15:00",
            'tue' => '',
            'wed' => '',
            'thu' => '',
            'fri' => '',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 30,
            'non_time_mode_minutes' => 0,
        ];

        // expected sorted plan
        $sorted_plan = [
            'mon' => [
                [
                    'task' => $task_a,
                    'start' => 660,
                    'end' => 690,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 30,
                ],
                [
                    'task' => $task_b,
                    'start' => 690,
                    'end' => 750,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 60,
                ],
                [
                    'task' => $task_c,
                    'start' => 750,
                    'end' => 870,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
            ],
            'tue' => [],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];

        // now the final distribution instance
        $distributor = new DistributionLogic($time_slots_config);
        $distributor->distributeTasks($init_tasks);
        $distributed_plan = $distributor->getTasksPlan()->getPlan();

        $this->assertSame(
            $sorted_plan,
            $distributed_plan,
            'DistributionLogic with Min Slot Length did not distribute tasks as expected.'
        );
    }

    /**
     * I had a bug that I created a pseudo blocking task from 9:00-17:00.
     * Then it was 7:45 and normally another task should still be planned,
     * until that point, but somehow this blocking task got shown from
     * 7:45-17:00 ...
     * With this test I try to solve this issue
     */
    public function testBlockingTaskWithNow(): void
    {
        $blocking_config = "mon 9:00-17:00";

        $task = TestTask::create(
            project_max_hours_day: 4,
            time_remaining: 1,
        );
        $init_tasks = [
            $task
        ];

        $time_slots_config = [
            'mon' => "6:00-8:30\n11:00-13:00",
            'tue' => '10:00-11:00',
            'wed' => '',
            'thu' => '',
            'fri' => '',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 30,
            'non_time_mode_minutes' => 0,
        ];

        // fakes "now"
        $time_point = new TimePoint('mon 7:45');

        // expected sorted plan
        $sorted_plan = [
            'mon' => [
                [
                    'task' => $task,
                    'start' => 465,
                    'end' => 510,
                    'length' => 45,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'tue' => [
                [
                    'task' => $task,
                    'start' => 600,
                    'end' => 615,
                    'length' => 15,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];

        // now the final distribution instance
        $distributor = new DistributionLogic($time_slots_config);
        // will fake a depletion of slots until "now", which is set above with
        // the $time_point variable, which should be 7:45 on Monday
        $distributor->depleteUntilTimePoint($time_point);
        $distributor->depleteByTimeSpansConfig($blocking_config, $time_point);
        $distributor->distributeTasks($init_tasks);
        $distributed_plan = $distributor->getTasksPlan()->getPlan();

        $this->assertSame(
            $sorted_plan,
            $distributed_plan,
            'DistributionLogic with blocking pseudo tasts and "now" did not distribute tasks as expected.'
        );
    }

    /**
     * Sometimes there might be tasks, which are still open, while
     * they do not have remaining time, but overtime already. Such
     * tasks should still be able to be planned so that it is clear
     * that such tasks aren't done yet.
     */
    public function testOpenTaskAndOvertime()
    {
        $time_slots_config = [
            'mon' => '0:00-10:00',
            'tue' => '',
            'wed' => '',
            'thu' => '',
            'fri' => '',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 30,
            'non_time_mode_minutes' => 30,
        ];

        $task_a = TestTask::create(
            title: 'a', time_estimated: 5.0, time_spent: 6.0,
            nb_completed_subtasks: 1, nb_subtasks: 2,
            project_max_hours_day: 10
        );
        $task_b = TestTask::create(
            title: 'b', time_estimated: 1.0, time_spent: 0.0, time_remaining: 1.0,
            nb_completed_subtasks: 0, nb_subtasks: 2,
            project_max_hours_day: 10
        );
        $init_tasks = [$task_a, $task_b];

        // expected sorted plan
        $expected_plan = [
            'mon' => [
                [
                    'task' => $task_a,
                    'start' => 0,
                    'end' => 30,
                    'length' => 30,
                    'spent' => 360,
                    'remaining' => 0,
                ],
                [
                    'task' => $task_b,
                    'start' => 30,
                    'end' => 90,
                    'length' => 60,
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
            'overflow' => [],
        ];

        // now the final distribution instance
        $distributor = new DistributionLogic($time_slots_config);
        $distributor->distributeTasks($init_tasks);
        $distributed_plan = $distributor->getTasksPlan()->getPlan();

        $this->assertSame(
            $expected_plan,
            $distributed_plan,
            'DistributionLogic with open tasks and overtime did not distribute tasks as expected.'
        );
    }
}
