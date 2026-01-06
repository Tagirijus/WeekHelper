<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TasksPlan.php';
require_once __DIR__ . '/../tests/TestTask.php';
require_once __DIR__ . '/../Helper/TimeSlotsDay.php';
require_once __DIR__ . '/../Helper/TimePoint.php';
require_once __DIR__ . '/../Helper/TimeSpan.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TasksPlan;
use Kanboard\Plugin\WeekHelper\tests\TestTask;
use Kanboard\Plugin\WeekHelper\Helper\TimeSlotsDay;


final class TasksPlanTest extends TestCase
{
    public function testTasksPlanRemainingMinutes()
    {
        $tasks_plan = new TasksPlan();

        //                         title, project_id, type,     max_hours, remain, spent
        $task = TestTask::create('a',   1,          'studio', 2,         2,      0);
        $time_slots_day_mon = new TimeSlotsDay("6:00-9:00 office\n11:00-13:00 studio", 'mon');
        $this->assertSame(
            120,
            $tasks_plan->minutesCanBePlanned($task, $time_slots_day_mon),
            'Task A could not be planned correctly on the given time slots day.'
        );
        // plan it
        $plan_success = $tasks_plan->planTask($task, $time_slots_day_mon);
        // now task a should be completely planned already
        $this->assertSame(
            0,
            $tasks_plan->getTasksActualRemaining($task),
            'Task A actual remaining is wrong.'
        );
        // and planning should be a success accordingly
        $this->assertTrue($plan_success, 'planTask() did not return true ...');
    }

    public function testTasksPlanDailyLimits()
    {
        $tasks_plan = new TasksPlan();

        //                         title, project_id, type,     max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          'studio', 2,         2,      0);
        $task   = TestTask::create('b',   1,          'studio', 2,         3,      0);
        $time_slots_day_mon = new TimeSlotsDay("6:00-9:00 office\n11:00-13:00 studio", 'mon');

        // plan the task to deplete the projects daily limit for this day
        $tasks_plan->planTask($task_a, $time_slots_day_mon);

        // first check the actual remaining, which should be still 180 minutes
        $this->assertSame(
            180,
            $tasks_plan->getTasksActualRemaining($task),
            'Task B actual remaining is wrong.'
        );
        // for monday the day limit is full for this project, though
        $this->assertSame(
            0,
            $tasks_plan->getLeftDailyTime($task, $time_slots_day_mon->getDay()),
            'Task B should not be able to be planned on Monday, since limit should be full for project.'
        );

        // now try to plan task b on some days
        $time_slots_day_tue = new TimeSlotsDay("6:00-9:00 office", 'tue');
        $this->assertSame(
            0,
            $tasks_plan->minutesCanBePlanned($task, $time_slots_day_tue),
            'Task B could be planned on the given time slots day, but should not be able to'
            . ' due to project type restriction. Time slot has "office", but task is from'
            . ' project with type "studio".'
        );
        $time_slots_day_wed = new TimeSlotsDay("6:00-7:00\n10:00-13:00 office", 'wed');
        $this->assertSame(
            60,
            $tasks_plan->minutesCanBePlanned($task, $time_slots_day_wed),
            'Task B should be able to be planned for 60 min on Wednesday, but could not.'
            . ' On Wednesday there is a non-type-restricting slot of 60 min available.'
        );
        $time_slots_day_thu = new TimeSlotsDay("6:00-9:00", 'thu');
        $this->assertSame(
            120,
            $tasks_plan->minutesCanBePlanned($task, $time_slots_day_thu),
            'Task B should be able to be planned for 120 min on Thursday, but could not.'
            . ' On Thursday there is a non-type-restricting slot available for 180 min.'
            . ' The project daily max is 120, though. So not the whole task should be'
            . ' able to plan on that day.'
        );
    }

    public function testTasksPlanRemainingMinutesMaxFromTask()
    {
        $tasks_plan = new TasksPlan();

        $task = TestTask::create('c', 3, '', 4, 0.5, 0.5);
        $time_slots_day = new TimeSlotsDay("6:00-9:00", 'mon');

        // initially the whole task, but nothing more should be
        // plannable on the timeslots first slot
        $this->assertSame(
            30,
            $tasks_plan->minutesCanBePlanned($task, $time_slots_day),
            'Task A has more or less minutes to be planned on the given time slot day.'
        );
    }

    public function testTasksPlanMinSlotLength()
    {
        $tasks_plan = new TasksPlan(15);

        $task_a = TestTask::create('a', 4, '', 4, 2.75, 0);
        $task_b = TestTask::create('b', 5, '', 4, 0.5, 0);
        $time_slots_day = new TimeSlotsDay("6:00-9:00", 'mon');

        // task A should fill up 2:45 hours, making the remaining
        // time for the lot to be 15 minutes
        $tasks_plan->planTask($task_a, $time_slots_day);

        // now task B should get these 15 minutes left
        $this->assertSame(
            15,
            $tasks_plan->minutesCanBePlanned($task_b, $time_slots_day),
            'Task B should now have 15 available minutes to be planned on that slot left.'
        );

        // but things change, if the threshold gets higher
        $tasks_plan->setMinSlotLength(16);

        // now the minutes to plan should be 0
        $this->assertSame(
            0,
            $tasks_plan->minutesCanBePlanned($task_b, $time_slots_day),
            'Task B should not have available minutes to be planned on that slot.'
        );

        // also the slot should now be depleted automatically
        $this->assertSame(
            0,
            $time_slots_day->getLengthOfSlot(0),
            'Time slot should be depleted and have 0 minutes in length in total.'
        );
    }

    public function testTasksPlanPlanningA()
    {
        $tasks_plan = new TasksPlan();

        // for Monday only task A should be planned
        $time_slots_day_mon = new TimeSlotsDay("6:00-9:00 office\n11:00-13:00 studio", 'mon');
        //                         title, project_id, type,     max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          'studio', 2,         2,      0);
        //                         title, project_id, type,     max_hours, remain, spent
        $task_b = TestTask::create('b',   1,          'studio', 2,         3,      0);

        // should be planned
        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        // should not be planned, since project max is reached already
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );

        // for Tuesday only 1 hour of task B should be planned
        $time_slots_day_tue = new TimeSlotsDay("10:00-11:00", 'tue');
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );

        // on Wednesday nothing should be planned
        $time_slots_day_wed = new TimeSlotsDay("6:00-9:00 office\n11:00-13:00 office", 'wed');
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_wed
        );

        // on Thursday the remaining 2 hours of task B should be planned,
        // but split into two different slots
        $time_slots_day_thu = new TimeSlotsDay("12:00-13:00\n16:00-17:00", 'thu');
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_thu
        );

        $this->assertSame(
            [
                'mon' => [[
                    'task' => $task_a,
                    'start' => 660,
                    'end' => 780,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]],
                'tue' => [[
                    'task' => $task_b,
                    'start' => 600,
                    'end' => 660,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 180,
                ]],
                'wed' => [],
                'thu' => [[
                    'task' => $task_b,
                    'start' => 720,
                    'end' => 780,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 180,
                ], [
                    'task' => $task_b,
                    'start' => 960,
                    'end' => 1020,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 180,
                ]],
                'fri' => [],
                'sat' => [],
                'sun' => [],
                'overflow' => [],
            ],
            $tasks_plan->getPlan(),
            'TasksPlan plan A is incorrect.'
        );
    }

    public function testTasksPlanPlanningBForMinSlotLength()
    {
        $tasks_plan = new TasksPlan(16);

        // for Monday only task A should be planned
        $time_slots_day_mon = new TimeSlotsDay("6:00-8:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("6:00-8:00", 'tue');
        //                         title, project_id, type, max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          '',   4,         1.75,   0);
        //                         title, project_id, type, max_hours, remain, spent
        $task_b = TestTask::create('b',   1,          '',   4,         2,      0);

        // should be planned
        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        // should not be planned, since min slot length is not fulfilled
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );
        // this should be planned instead
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );

        $this->assertSame(
            [
                'mon' => [[
                    'task' => $task_a,
                    'start' => 360,
                    'end' => 465,
                    'length' => 105,
                    'spent' => 0,
                    'remaining' => 105,
                ]],
                'tue' => [[
                    'task' => $task_b,
                    'start' => 360,
                    'end' => 480,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]],
                'wed' => [],
                'thu' => [],
                'fri' => [],
                'sat' => [],
                'sun' => [],
                'overflow' => [],
            ],
            $tasks_plan->getPlan(),
            'TasksPlan plan B is incorrect.'
        );
    }

    public function testMinSlotLengthAgain()
    {
        $tasks_plan = new TasksPlan(30);

        $time_slots_day_mon = new TimeSlotsDay("6:00-6:20\n10:00-12:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("6:00-8:00", 'tue');
        //                          title, project_id, type, max_hours, remain, spent
        $task_a1 = TestTask::create('a1',  1,          '',   4,         0.5,   0);
        $task_a2 = TestTask::create('a2',  1,          '',   4,         1.5,   0);
        $task_b  = TestTask::create('b',   1,          '',   4,         2,      0);

        // should be planned on Monday both
        $tasks_plan->planTask(
            $task_a1,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_a2,
            $time_slots_day_mon
        );
        // should not be planned on Monday
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );
        // should be planned on Monday already
        $tasks_plan->planTask(
            $task_a1,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_a2,
            $time_slots_day_tue
        );
        // should not be planned on Tuesday now
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );

        $this->assertSame(
            [
                'mon' => [[
                    'task' => $task_a1,
                    'start' => 600,
                    'end' => 630,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 30,
                ],
                [
                    'task' => $task_a2,
                    'start' => 630,
                    'end' => 720,
                    'length' => 90,
                    'spent' => 0,
                    'remaining' => 90,
                ]],
                'tue' => [[
                    'task' => $task_b,
                    'start' => 360,
                    'end' => 480,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]],
                'wed' => [],
                'thu' => [],
                'fri' => [],
                'sat' => [],
                'sun' => [],
                'overflow' => [],
            ],
            $tasks_plan->getPlan(),
            'TasksPlan plan with min slot length is incorrect.'
        );
    }

    public function testWorkedMode()
    {
        $tasks_plan = new TasksPlan();

        $time_slots_day_mon = new TimeSlotsDay("10:00-14:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("10:00-14:00", 'tue');

        //                         title, project_id, type, max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          '',   4,         4,      0);
        //                         title, project_id, type, max_hours, remain, spent
        $task_b = TestTask::create('b',   1,          '',   4,         2,      0);
        //                         title, project_id, type, max_hours, remain, spent
        $task_c = TestTask::create('c',   2,          '',   2,         2,      0);

        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_tue
        );

        // without looking at spent time, the plan should be this basically
        $expected_without_worked_mode = [
            'mon' => [
                [
                    'task' => $task_a,
                    'start' => 600,
                    'end' => 840,
                    'length' => 240,
                    'spent' => 0,
                    'remaining' => 240,
                ]
            ],
            'tue' => [
                [
                    'task' => $task_b,
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_c,
                    'start' => 720,
                    'end' => 840,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]
            ],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];
        $this->assertSame(
            $expected_without_worked_mode,
            $tasks_plan->getPlan(),
            'Expected plan is not correct without work mode enabled.'
        );

        //
        // now the same with work mode enabled and timeslots depleted
        // for Monday (as if the new day is Tuesday now after working
        // Monday already - but task a was not finished).
        //

        $tasks_plan = new TasksPlan();
        $tasks_plan_worked = new TasksPlan(0, true);

        $time_slots_day_mon = new TimeSlotsDay("10:00-14:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("10:00-14:00", 'tue');
        $time_slots_day_ovr = new TimeSlotsDay("0:00-100:00", 'overflow');

        //                         title, project_id, type, max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          '',   4,         2,      2);
        //                         title, project_id, type, max_hours, remain, spent
        $task_b = TestTask::create('b',   1,          '',   4,         2,      0);
        //                         title, project_id, type, max_hours, remain, spent
        $task_c = TestTask::create('c',   2,          '',   2,         2,      0);

        // create a plan for worked tasks and thus create a correct project
        // daily limits array internally ...
        // only do this for task A, since I just know that the other tasks
        // do not have spent time ... at least here in the test. later in the
        // distribution logic method, the method will iter through all
        // time slot day intsnaces anyway.
        $tasks_plan_worked->planTask(
            $task_a,
            $time_slots_day_mon
        );

        // pass the project daily limits to the actual tasks plan
        $tasks_plan->copyPlannedProjectTimesFromTasksPlan($tasks_plan_worked);

        // also deplete Monday as if it is Tuesday now already
        $time_slots_day_mon->deplete();

        // now plan the tasks again for the actual tasksplan instance
        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_ovr
        );

        // without looking at spent time, the plan should be this basically
        $expected_without_worked_mode = [
            'mon' => [],
            'tue' => [
                [
                    'task' => $task_a,
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 120,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_b,
                    'start' => 720,
                    'end' => 840,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]
            ],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [
                [
                    'task' => $task_c,
                    'start' => 0,
                    'end' => 120,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]
            ],
        ];
        $this->assertSame(
            $expected_without_worked_mode,
            $tasks_plan->getPlan(),
            'Expected plan is not correct with work mode enabled.'
        );
    }

    public function testGlobalTimes()
    {
        $tasks_plan = new TasksPlan();

        $time_slots_day_mon = new TimeSlotsDay("10:00-14:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("10:00-13:00", 'tue');
        $time_slots_day_ovr = new TimeSlotsDay("0:00-100:00", 'overflow');

        //                         title, project_id, type, max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          '',   4,         2,      1.5);
        //                         title, project_id, type, max_hours, remain, spent
        $task_b = TestTask::create('b',   1,          '',   4,         4,      1);
        //                         title, project_id, type, max_hours, remain, spent
        $task_c = TestTask::create('c',   2,          '',   2,         2,      0.5);

        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_tue
        );
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_ovr
        );

        $this->assertSame(
            480,
            $tasks_plan->getGlobalTimesForWeek()['remaining'],
            'Global times from TaskPlan are not the same.'
        );
        $this->assertSame(
            180,
            $tasks_plan->getGlobalTimesForWeek()['spent'],
            'Global times from TaskPlan are not the same.'
        );
        $this->assertSame(
            420,
            $tasks_plan->getGlobalTimesForWeek()['planned'],
            'Global times from TaskPlan are not the same.'
        );
        $this->assertSame(
            60,
            $tasks_plan->getGlobalTimesForOverflow()['planned'],
            'Global times from TaskPlan are not the same.'
        );
    }

    public function testEarliestStart()
    {
        $tasks_plan = new TasksPlan();

        $time_slots_day_mon = new TimeSlotsDay("10:00-14:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("10:00-14:00", 'tue');

        //                         title, project_id, type, max_hours, remain, spent
        $task_a = TestTask::create('a',   1,          '',   4,         2,      0);
        // also task A should have a "plan_from" value set; which will be
        // parsed normally later in the whole automatic planning process; only for
        // the tets I will set this value by hand
        $task_a['plan_from'] = 'tue 11:00';
        //                         title, project_id, type, max_hours, remain, spent
        $task_b = TestTask::create('b',   1,          '',   4,         4,      0);
        //                         title, project_id, type, max_hours, remain, spent
        $task_c = TestTask::create('c',   2,          '',   2,         2,      0);

        // now I plan manually like the sorting was set already and how I know how
        // the tasks should be planned across the days; at least how it is supposed
        // to happen

        // for task A, for example, it should not be planned on Monday, since its
        // earliest start is set to Tuesday 11:00!
        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        // go on with the next tasks then ...
        // task B should be planned
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );
        // task C shoudl not be planned
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_mon
        );
        // now its Tuesday, only task A and C should be remaining
        // task A should be planned on 11:00-13:00
        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_tue
        );
        // task B should be depleted by now already, thus not planned
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_tue
        );
        // task C should be planned before that on 10:00-11:00
        // and also after that on 13:00-14:00
        $tasks_plan->planTask(
            $task_c,
            $time_slots_day_tue
        );

        // now let's see if everythig worked correctly
        $expected = [
            'mon' => [
                [
                    'task' => $task_b,
                    'start' => 600,
                    'end' => 840,
                    'length' => 240,
                    'spent' => 0,
                    'remaining' => 240,
                ]
            ],
            'tue' => [
                [
                    'task' => $task_c,
                    'start' => 600,
                    'end' => 660,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_a,
                    'start' => 660,
                    'end' => 780,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => $task_c,
                    'start' => 780,
                    'end' => 840,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 120,
                ]
            ],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];

        $this->assertSame(
            $expected,
            $tasks_plan->getPlan(),
            'Expected plan is not correct with plan_from set for task A.'
        );
    }

    public function testCombinePlan()
    {
        $plan_a = [
            'mon' => [
                [
                    'task' => ['title' => 'plan a mon 1'],
                    'start' => 300,
                    'end' => 360,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => ['title' => 'plan a mon 2'],
                    'start' => 1200,
                    'end' => 1300,
                    'length' => 100,
                    'spent' => 0,
                    'remaining' => 100,
                ]
            ],
            'tue' => [],
            'wed' => [
                [
                    'task' => ['title' => 'plan a wed 1'],
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ]
            ],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];
        $plan_b = [
            'mon' => [
                [
                    'task' => ['title' => 'plan b mon 1'],
                    'start' => 360,
                    'end' => 420,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'tue' => [
                [
                    'task' => ['title' => 'plan b tue 1'],
                    'start' => 300,
                    'end' => 420,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => ['title' => 'plan b tue 2'],
                    'start' => 420,
                    'end' => 480,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'wed' => [
                [
                    'task' => ['title' => 'plan b wed 1'],
                    'start' => 900,
                    'end' => 930,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];
        $expected_combine = [
            'mon' => [
                [
                    'task' => ['title' => 'plan a mon 1'],
                    'start' => 300,
                    'end' => 360,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => ['title' => 'plan b mon 1'],
                    'start' => 360,
                    'end' => 420,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 60,
                ],
                [
                    'task' => ['title' => 'plan a mon 2'],
                    'start' => 1200,
                    'end' => 1300,
                    'length' => 100,
                    'spent' => 0,
                    'remaining' => 100,
                ]
            ],
            'tue' => [
                [
                    'task' => ['title' => 'plan b tue 1'],
                    'start' => 300,
                    'end' => 420,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => ['title' => 'plan b tue 2'],
                    'start' => 420,
                    'end' => 480,
                    'length' => 60,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'wed' => [
                [
                    'task' => ['title' => 'plan a wed 1'],
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 120,
                ],
                [
                    'task' => ['title' => 'plan b wed 1'],
                    'start' => 900,
                    'end' => 930,
                    'length' => 30,
                    'spent' => 0,
                    'remaining' => 60,
                ]
            ],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];

        $this->assertSame(
            $expected_combine,
            TasksPlan::combinePlans($plan_a, $plan_b),
            'TasksPlan combiner returned something incorrect.'
        );
    }
}
