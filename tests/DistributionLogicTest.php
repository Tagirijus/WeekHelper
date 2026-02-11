<?php

declare(strict_types=1);

require_once __DIR__ . '/../tests/TestTask.php';
require_once __DIR__ . '/../Model/DistributionLogic.php';
require_once __DIR__ . '/../Model/ProjectQuota.php';
require_once __DIR__ . '/../Model/TasksPlan.php';
require_once __DIR__ . '/../Model/TasksTimesPreparer.php';
require_once __DIR__ . '/../Model/TimesCalculator.php';
require_once __DIR__ . '/../Model/TimeSlotsDay.php';
require_once __DIR__ . '/../Model/TimeSpan.php';
require_once __DIR__ . '/../Model/TimePoint.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\DistributionLogic;
use Kanboard\Plugin\WeekHelper\Model\TasksTimesPreparer;
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

    /**
     * This test is for preparing a new internal logic for distributing
     * tasks. It can happen that I work in advance for certain tasks,
     * even if the daily limit would not allow it. Sometimes I might
     * have some more time than expected. In that case I work on a task.
     * For now the system handles such things like that: it basically
     * gets the spent time and "distributes" it throughout the tasks
     * from the start of the week until the end.
     *
     * Example scenario:
     * I have a daily limit of 1h per day. A task has an estimated for
     * 5h for the whole week. It could be done for 1h each day, basically.
     * Now on Monday I found time to even work 2h for this task. This would
     * mean that the system would get "2h of spent time" and thus "distribute"
     * this spent time throughout Monday and Tuesday. Now it might be start of
     * Tuesday and I alrwady worked 2h for this task. This would mean that
     * the system would not plan this task for this day (Tuesday), since it
     * already got 2h of spent time, thinking I might have worked these 2h
     * on Monday+Tuesday already.
     *
     * New feature / logic idea:
     * I might consider adding this as an optional logic, since I am not sure
     * right now, if it otherwise would break some backwards-compability.
     * Now the new logic I want to have is that the system would "take" this
     * "worked in advance" form the end of the week and not from the start.
     * Means for the above example that not Tuesday would lack of the 1h I
     * worked in advance already, but Friday.
     *
     * Possible solution:
     * I introduced whole new classes: ProjectQuota and ProjectQuotaAll, which
     * are being used by TasksPlan to get the actual project daily limits.
     * DistributionLogic can modify this ProjectQuotaAll instance and pass it
     * to the TasksPlan so that TasksPlan will use this for planning then,
     * regardless of the actual tasks array data about the project daily limits
     * (how it was before, basically!). This way my above scenario should be
     * possible as wanted.
     *
     * Future idea:
     * In case I want to have different kind of logics if it comes to working
     * in advance, maybe I do want the program to fetch available time from
     * start of the week. Then I could create a new config which could handle
     * this and this would change the whole logic happening inside the method
     * DistribbutionLogic->depleteProjectQuota().
     *
     * STILL PERSISTING PROBLEM:
     * For now the TimePoint in depleteProjectQuota() is for filling the spent
     * time for the project quota before that. After that is "filled" (depleted)
     * the end of the week is to fill until the start towards the TimePoint.
     * Per day this might be okayish, but if it comes to the exact time point of
     * the day, it is not that precise, since there could be time slots, which
     * wold not allow spent time per project, for example. But at the moment I
     * do not have a good solution for considering this as well. So for now
     * this "work in advance" mode is non-precise and allows spent time to be
     * spent on time slots, which normally cannot plan such tasks from this
     * project (maybe). In generall it just lowers the project daily limit
     * only - the planned tasks in the end can still only be planned on the
     * specific time slots, but it can be non-precise in some situations ...
     * ALSO I just realized that with the current algorithm it can also mean
     * that two projects for which I worked in advance could have this time
     * slot time before TimePoint to be used as "yeah, this task was spent here",
     * meaning that in 1h allegedly both projects worked in advance-time could
     * have been done. Another thing I cannot and do not want to solve now.
     * It really feels like I should have come up with an overall completely
     * other business logic in general, for the WHOLE automatic planning ...
     */
    public function testTasksPlanWorkedInAdvance()
    {
        // in my test it will be Wednesday 1:00. For this task on this
        // time I could have normally worked 6h, but I did work 8h
        // for it. Also the 1h before TimePoint of 1:00 would not be
        // a time slot on which this task normally could have been
        // planned - but for now this algorithm is non-precise, since I
        // fear that I otherwise had to fully refactore huge parts of
        // the code. So this 1h is considered to be time the task could
        // have been worked for anyway. So it's 7h of work, which could
        // have been spent, according to this algorithm. So in that case
        // the extra 1h of work now should be fetched from Friday so that
        // there should still be 2h be planned on Wednesday for this task
        // (but from slot 2, of course!). Friday should have 2h of task be
        // planned accordingly.
        $task_1 = TestTask::create(
            column_name: 'col_a',
            project_id: 1,
            project_max_hours_day: 3,
            project_type: 'a',
            time_estimated: 15.0,
            time_spent: 8.0,
            time_remaining: 7.0,
            title: '1',
        );
        // since I will assume it is Wednesday already, this task
        // should technically create 5h of overflow already, because
        // according to this task I did not work on it already, but
        // normally I should have for 5 hours, when it is Wednesday 1:00.
        // Now I don't and the first time slot on Wednesday is just for this
        // task, but only 1 h is left and the next time slot will be filled
        // by task a already; leaving only 1h to be planned on Wednesday for
        // this task and leaving 9h to be planned on Thu and Fri; so only 4h
        // available planning time. 9-4=5
        $task_2 = TestTask::create(
            column_name: 'col_a',
            project_id: 2,
            project_max_hours_day: 2,
            project_type: 'b',
            time_estimated: 10.0,
            time_spent: 0.0,
            time_remaining: 10.0,
            title: '2',
        );
        $init_tasks = [
            $task_1,
            $task_2,
        ];

        // I need the TasksTimesPreparer for this to work;
        // virtually I assigned some level, since I need
        // this in the TasksTimesPreparer, which stores
        // certain times level depending I want to access
        // later in real scenarios.
        $config = [
            'levels_config' => [
                'level_1' => 'col_a',
            ]
        ];
        $ttp = new TasksTimesPreparer($config);

        // now the final distribution instance
        $time_slots_config = [
            'mon' => '0:00-5:00',
            'tue' => '0:00-5:00',
            'wed' => "0:00-2:00 b\n2:00-5:00",
            'thu' => '0:00-5:00',
            'fri' => '0:00-5:00',
            'sat' => '',
            'sun' => '',
            'min_slot_length' => 0,
            'non_time_mode_minutes' => 0,
        ];
        $distributor = new DistributionLogic($time_slots_config);

        // and this part is, if I would distribute for the active
        // week, while it would be Wednesday, 1:00 o'clock
        $now = new TimePoint('wed 1:00');
        $distributor->depleteUntilTimePoint($now);
        $distributor->depleteProjectQuota($ttp, 'level_1', $now);

        // final disgtribution of the tasks
        $distributor->distributeTasks($init_tasks);
        $distributed_plan = $distributor->getTasksPlan()->getPlan();

        // expected plan
        $expected_plan = [
            'mon' => [],
            'tue' => [],
            'wed' => [
                [
                    'task' => $task_2,
                    'start' => 60,
                    'end' => 120,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 600,
                ],
                [
                    'task' => $task_1,
                    'start' => 120,
                    'end' => 240,
                    'length' => 120,
                    'spent' => 480,
                    'remaining' => 420,
                ],
            ],
            'thu' => [
                [
                    'task' => $task_1,
                    'start' => 0,
                    'end' => 180,
                    'length' => 180,
                    'spent' => 480,
                    'remaining' => 420,
                ],
                [
                    'task' => $task_2,
                    'start' => 180,
                    'end' => 300,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 600,
                ],
            ],
            'fri' => [
                [
                    'task' => $task_1,
                    'start' => 0,
                    'end' => 120,
                    'length' => 120,
                    'spent' => 480,
                    'remaining' => 420,
                ],
                [
                    'task' => $task_2,
                    'start' => 120,
                    'end' => 240,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 600,
                ],
            ],
            'sat' => [],
            'sun' => [],
            'overflow' => [
                [
                    'task' => $task_2,
                    'start' => 0,
                    'end' => 300,
                    'length' => 300,
                    'spent' => 0,
                    'remaining' => 600,
                ],
            ],
        ];

        $this->assertSame(
            $expected_plan,
            $distributed_plan,
            'DistributionLogic did not distribute tasks as expected with worked in advance scenario.'
        );
    }
}
