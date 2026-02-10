<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TasksPlan.php';
require_once __DIR__ . '/../tests/TestTask.php';
require_once __DIR__ . '/../Model/TimesCalculator.php';
require_once __DIR__ . '/../Model/TimeSlotsDay.php';
require_once __DIR__ . '/../Model/TimePoint.php';
require_once __DIR__ . '/../Model/TimeSpan.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TasksPlan;
use Kanboard\Plugin\WeekHelper\tests\TestTask;
use Kanboard\Plugin\WeekHelper\Model\TimeSlotsDay;
use Kanboard\Plugin\WeekHelper\Model\TimePoint;


final class TasksPlanTest extends TestCase
{
    public function testTasksPlanRemainingMinutes()
    {
        $tasks_plan = new TasksPlan();

        $task = TestTask::create(
            project_max_hours_day: 2,
            project_type: 'studio',
            time_remaining: 2,
        );
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

    public function testTasksPlanRemainingMinutesOpenOvertimeTask()
    {
        $tasks_plan = new TasksPlan(non_time_mode_minutes: 15);

        $task = TestTask::create(
            title: 'over, uh oh', time_estimated: 5.0, time_spent: 6.0,
            nb_completed_subtasks: 1, nb_subtasks: 2,
            project_max_hours_day: 10
        );
        $time_slots_day_mon = new TimeSlotsDay('0:00-10:00', 'mon');
        $this->assertSame(
            15,
            $tasks_plan->minutesCanBePlanned($task, $time_slots_day_mon),
            'Open + overtime task gets wrong "minutes can be planned".'
        );
        // plan it
        $plan_success = $tasks_plan->planTask($task, $time_slots_day_mon);
        // now task a should be completely planned already
        $this->assertSame(
            0,
            $tasks_plan->getTasksActualRemaining($task),
            'Open + overtime task actual remaining is wrong.'
        );
    }

    public function testTasksPlanDailyLimits()
    {
        $tasks_plan = new TasksPlan();

        $task_a = TestTask::create(
            project_max_hours_day: 2,
            project_type: 'studio',
            time_remaining: 2,
            title: 'a',
        );
        $task   = TestTask::create(
            project_max_hours_day: 2,
            project_type: 'studio',
            time_remaining: 3,
            title: 'b',
        );
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

    public function testTasksPlanDailyLimitsPerDay()
    {
        $tasks_plan = new TasksPlan();

        // this makes the default project_max_hours_day to 2,
        // but the Wednesday should be 4
        $task   = TestTask::create(
            project_max_hours_day: 2,
            project_max_hours_wed: 4,
            time_remaining: 13,
        );

        // Monday should get 2 hours, Tuesday 2 hours, but Wednesday 4 hours,
        // Thursday 2 hours again, and the rest goes to overflow: 3 hours
        $time_slots_day_mon = new TimeSlotsDay("10:00-20:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("10:00-20:00", 'tue');
        $time_slots_day_wed = new TimeSlotsDay("10:00-20:00", 'wed');
        $time_slots_day_thu = new TimeSlotsDay("10:00-20:00", 'thu');
        $time_slots_day_fri = new TimeSlotsDay("0:00-0:00", 'fri');
        $time_slots_day_ovr = new TimeSlotsDay("0:00-100:00", 'overflow');

        // plan the task to deplete the projects daily limit for this day
        $tasks_plan->planTask($task, $time_slots_day_mon);
        $tasks_plan->planTask($task, $time_slots_day_tue);
        $tasks_plan->planTask($task, $time_slots_day_wed);
        $tasks_plan->planTask($task, $time_slots_day_thu);
        $tasks_plan->planTask($task, $time_slots_day_fri);
        $tasks_plan->planTask($task, $time_slots_day_ovr);

        // expected plan
        $expected_plan = [
            'mon' => [
                [
                    'task' => $task,
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 780,
                ]
            ],
            'tue' => [
                [
                    'task' => $task,
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 780,
                ]
            ],
            'wed' => [
                [
                    'task' => $task,
                    'start' => 600,
                    'end' => 840,
                    'length' => 240,
                    'spent' => 0,
                    'remaining' => 780,
                ]
            ],
            'thu' => [
                [
                    'task' => $task,
                    'start' => 600,
                    'end' => 720,
                    'length' => 120,
                    'spent' => 0,
                    'remaining' => 780,
                ]
            ],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [
                [
                    'task' => $task,
                    'start' => 0,
                    'end' => 180,
                    'length' => 180,
                    'spent' => 0,
                    'remaining' => 780,
                ]
            ],
        ];

        // now let's see if the plan is correct
        $this->assertSame(
            $expected_plan,
            $tasks_plan->getPlan(),
            'TaskPlan was not able to plan with the correct individual daily limit.'
        );
    }

    public function testTasksPlanRemainingMinutesMaxFromTask()
    {
        $tasks_plan = new TasksPlan();

        $task = TestTask::create(
            project_max_hours_day: 4,
            time_remaining: 0.5,
            time_spent: 0.5,
        );
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
        $tasks_plan = new TasksPlan(min_slot_length: 15);

        $task_a = TestTask::create(
            project_id: 4,
            project_max_hours_day: 4,
            time_remaining: 2.75,
            title: 'a',
        );
        $task_b = TestTask::create(
            project_id: 5,
            project_max_hours_day: 4,
            time_remaining: 0.5,
            title: 'b',
        );
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
        $task_a = TestTask::create(
            project_max_hours_day: 2,
            project_type: 'studio',
            time_remaining: 2,
            title: 'a',
        );
        $task_b = TestTask::create(
            project_max_hours_day: 2,
            project_type: 'studio',
            time_remaining: 3,
            title: 'b',
        );

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
        $tasks_plan = new TasksPlan(min_slot_length: 16);

        // for Monday only task A should be planned
        $time_slots_day_mon = new TimeSlotsDay("6:00-8:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("6:00-8:00", 'tue');

        $task_a = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            time_remaining: 1.75,
            title: 'a',
        );
        $task_b = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            time_remaining: 2,
            title: 'b',
        );

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
        $tasks_plan = new TasksPlan(min_slot_length: 30);

        $time_slots_day_mon = new TimeSlotsDay("6:00-6:20\n10:00-12:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("6:00-8:00", 'tue');
        //                          title, project_id, type, max_hours, remain, spent
        $task_a1 = TestTask::create(
            project_max_hours_day: 4,
            time_remaining: 0.5,
            title: 'a1',
        );
        $task_a2 = TestTask::create(
            project_max_hours_day: 4,
            time_remaining: 1.5,
            title: 'a2',
        );
        $task_b  = TestTask::create(
            project_max_hours_day: 4,
            time_remaining: 2,
            title: 'b',
        );

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

    public function testGlobalTimes()
    {
        $tasks_plan = new TasksPlan();

        $time_slots_day_mon = new TimeSlotsDay("10:00-14:00", 'mon');
        $time_slots_day_tue = new TimeSlotsDay("10:00-13:00", 'tue');
        $time_slots_day_ovr = new TimeSlotsDay("0:00-100:00", 'overflow');

        $task_a = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            time_remaining: 2,
            time_spent: 1.5,
            title: 'a',
        );
        $task_b = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            time_remaining: 4,
            time_spent: 1,
            title: 'b',
        );
        $task_c = TestTask::create(
            project_id: 2,
            project_max_hours_day: 2,
            time_remaining: 2,
            time_spent: 0.5,
            title: 'c',
        );

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
        $task_a = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            time_remaining: 2,
            title: 'a',
        );
        // also task A should have a "plan_from" value set; which will be
        // parsed normally later in the whole automatic planning process; only for
        // the tests I will set this value by hand
        $task_a['plan_from'] = 'tue 11:00';
        //                         title, project_id, type, max_hours, remain, spent
        $task_b = TestTask::create(
            project_id: 1,
            project_max_hours_day: 4,
            time_remaining: 4,
            title: 'b',
        );
        //                         title, project_id, type, max_hours, remain, spent
        $task_c = TestTask::create(
            project_id: 2,
            project_max_hours_day: 2,
            time_remaining: 2,
            title: 'c',
        );

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

    /**
     * Sometimes there might be tasks, which are still open, while
     * they do not have remaining time, but overtime already. Such
     * tasks should still be able to be planned so that it is clear
     * that such tasks aren't done yet.
     */
    public function testOpenTaskAndOvertimeA()
    {
        $tasks_plan = new TasksPlan(non_time_mode_minutes: 30);

        $time_slots_day_mon = new TimeSlotsDay('0:00-10:00', 'mon');

        $task_a = TestTask::create(
            title: 'a', time_estimated: 5.0, time_spent: 6.0,
            nb_completed_subtasks: 1, nb_subtasks: 2,
            project_max_hours_day: 10,
        );
        $task_b = TestTask::create(
            title: 'b', time_estimated: 1.0, time_spent: 0.0, time_remaining: 1.0,
            nb_completed_subtasks: 0, nb_subtasks: 2,
            project_max_hours_day: 10,
        );

        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );

        // expected plan
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

        $this->assertSame(
            $expected_plan,
            $tasks_plan->getPlan(),
            'TasksPlan with open tasks and overtime did not plan tasks as expected.'
        );
    }

    /**
     * It's like testOpenTaskAndOvertimeA(), but with no
     * non_time_mode_minutes given, resulting in 5 min for it.
     */
    public function testOpenTaskAndOvertimeB()
    {
        $tasks_plan = new TasksPlan();

        $time_slots_day_mon = new TimeSlotsDay('0:00-10:00', 'mon');

        $task_a = TestTask::create(
            title: 'a', time_estimated: 5.0, time_spent: 6.0,
            nb_completed_subtasks: 1, nb_subtasks: 2,
            project_max_hours_day: 10,
        );
        $task_b = TestTask::create(
            title: 'b', time_estimated: 1.0, time_spent: 0.0, time_remaining: 1.0,
            nb_completed_subtasks: 0, nb_subtasks: 2,
            project_max_hours_day: 10,
        );

        $tasks_plan->planTask(
            $task_a,
            $time_slots_day_mon
        );
        $tasks_plan->planTask(
            $task_b,
            $time_slots_day_mon
        );

        // expected plan
        $expected_plan = [
            'mon' => [
                [
                    'task' => $task_a,
                    'start' => 0,
                    'end' => 5,
                    'length' => 5,
                    'spent' => 360,
                    'remaining' => 0,
                ],
                [
                    'task' => $task_b,
                    'start' => 5,
                    'end' => 65,
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

        $this->assertSame(
            $expected_plan,
            $tasks_plan->getPlan(),
            'TasksPlan with open tasks and overtime did not plan tasks as expected, when no non_time_mode_minutes is given.'
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
     * I should keep the "worked in advance" time and substract it from the
     * end of the week. I have to dig into the code and understand it, but
     * I guess it is about the "planned_project_times" variable, which also
     * basically stands for the spent time somehow ... I am still thinking ...
     */
    public function TODOtestWorkedInAdvance()
    {
        $tasks_plan = new TasksPlan(now: new TimePoint('wed 6:00'));

        $time_slots_day_mon = new TimeSlotsDay('0:00-10:00', 'mon');
        $time_slots_day_tue = new TimeSlotsDay('0:00-10:00', 'tue');
        $time_slots_day_wed = new TimeSlotsDay('0:00-10:00', 'wed');

        $task = TestTask::create(
            title: 'task', time_estimated: 3.0, time_spent: 2.0, time_remaining: 1.0,
            project_max_hours_day: 1
        );

        $tasks_plan->planTask($task, $time_slots_day_mon);
        $tasks_plan->planTask($task, $time_slots_day_tue);
        $tasks_plan->planTask($task, $time_slots_day_wed);

        $expected = [
            'mon' => [
                [
                    'task' => $task,
                    'start' => 0,
                    'end' => 60,
                    'length' => 60,
                    'spent' => 120,
                    'remaining' => 60,
                ],
            ],
            'tue' => [
                [
                    'task' => $task,
                    'start' => 0,
                    'end' => 60,
                    'length' => 60,
                    'spent' => 120,
                    'remaining' => 60,
                ],
            ],
            'wed' => [
                [
                    'task' => $task,
                    'start' => 0,
                    'end' => 60,
                    'length' => 60,
                    'spent' => 120,
                    'remaining' => 60,
                ],
            ],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];

        $this->assertSame(
            $expected,
            $tasks_plan->getPlan(),
            'TasksPlan does not plan correctly with "worked in advance" spent times.'
        );
    }
}
