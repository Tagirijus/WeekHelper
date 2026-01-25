<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TasksTimesPreparer.php';
require_once __DIR__ . '/../tests/TestTask.php';
require_once __DIR__ . '/../Model/SortingLogic.php';
require_once __DIR__ . '/../Model/TaskDataExtender.php';
require_once __DIR__ . '/../Model/TimesCalculator.php';
require_once __DIR__ . '/../Model/TimesData.php';
require_once __DIR__ . '/../Model/TimesDataPerEntity.php';
require_once __DIR__ . '/../Model/TimetaggerFetcher.php';
require_once __DIR__ . '/../Model/TimetaggerTranscriber.php';


use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TasksTimesPreparer;
use Kanboard\Plugin\WeekHelper\tests\TestTask;


final class TasksTimesPreparerTest extends TestCase
{
    public function testSimpleTasksTimes()
    {
        $ttp = new TasksTimesPreparer();

        $tasks = [
            TestTask::create(
                time_estimated: 3,
                time_spent: 0.25,
            ),
            TestTask::create(
                time_estimated: 6,
                time_spent: 0.25,
            )
        ];
        $ttp->initTasksAndTimes($tasks);

        // total times
        $this->assertSame(
            9.0,
            $ttp->getEstimatedTotal(),
            'TaskTimesPreparer->getEstimatedTotal() returned wrong value.'
        );
        $this->assertSame(
            0.5,
            $ttp->getSpentTotal(),
            'TaskTimesPreparer->getSpentTotal() returned wrong value.'
        );
        $this->assertSame(
            8.5,
            $ttp->getRemainingTotal(),
            'TaskTimesPreparer->getRemainingTotal() returned wrong value.'
        );
        $this->assertSame(
            0.0,
            $ttp->getOvertimeTotal(),
            'TaskTimesPreparer->getOvertimeTotal() returned wrong value.'
        );
    }

    public function testNonTimeMode()
    {
        $config = [
            'non_time_mode_minutes' => 10
        ];
        $ttp = new TasksTimesPreparer($config);

        $tasks = [
            TestTask::create(
                score: 6,  // is 1 hour estimated
            ),
            TestTask::create(
                score: 12,  // is 2 hours estimated
            )
        ];
        $subtasks_by_task_ids = [
            $tasks[0]['id'] => [
                TestTask::createSub(status: 1)  // 50% of 1 hour spent: 0.5 spent
            ],
            $tasks[1]['id'] => [
                TestTask::createSub(status: 2, title: '50%'),  // is 1 hour spent
                TestTask::createSub(status: 1),  // is 50% of the remaining 25% spent: 0.25 hours
                TestTask::createSub(status: 0),  // is 0 hours spent
            ],
        ];
        $ttp->initTasksAndTimes($tasks, $subtasks_by_task_ids);

        // total times
        $this->assertSame(
            3.0,
            $ttp->getEstimatedTotal(),
            'TaskTimesPreparer->getEstimatedTotal() returned wrong value with non-time-mode.'
        );
        $this->assertSame(
            1.75,
            $ttp->getSpentTotal(),
            'TaskTimesPreparer->getSpentTotal() returned wrong value with non-time-mode.'
        );
        $this->assertSame(
            1.25,
            $ttp->getRemainingTotal(),
            'TaskTimesPreparer->getRemainingTotal() returned wrong value with non-time-mode.'
        );
        $this->assertSame(
            0.0,
            $ttp->getOvertimeTotal(),
            'TaskTimesPreparer->getOvertimeTotal() returned wrong value with non-time-mode.'
        );
    }

    public function testSubtasksTimesA()
    {
        $ttp = new TasksTimesPreparer();

        $tasks = [
            TestTask::create(
                time_estimated: 3,
                time_spent: 0.25,
            ),
            TestTask::create(
                time_estimated: 6,
                time_spent: 0.25,
            )
        ];
        $subtasks_by_task_ids = [
            $tasks[0]['id'] => [
                TestTask::createSub(
                    time_estimated: 5,
                    time_spent: 1
                )
            ],
            $tasks[1]['id'] => [
                TestTask::createSub(
                    time_estimated: 2.5,
                    time_spent: 0.5
                ),
                TestTask::createSub(
                    time_estimated: 3,
                    time_spent: 3
                ),
            ],
        ];
        $ttp->initTasksAndTimes($tasks, $subtasks_by_task_ids);

        // total times
        $this->assertSame(
            10.5,
            $ttp->getEstimatedTotal(),
            'TaskTimesPreparer->getEstimatedTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            4.5,
            $ttp->getSpentTotal(),
            'TaskTimesPreparer->getSpentTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            6.0,
            $ttp->getRemainingTotal(),
            'TaskTimesPreparer->getRemainingTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            0.0,
            $ttp->getOvertimeTotal(),
            'TaskTimesPreparer->getOvertimeTotal() returned wrong value with subtasks.'
        );
    }

    public function testSubtasksTimesB()
    {
        $ttp = new TasksTimesPreparer();

        $tasks = [
            TestTask::create(),
            TestTask::create()
        ];
        $subtasks_by_task_ids = [
            $tasks[0]['id'] => [
                TestTask::createSub(
                    status: 1,
                    time_estimated: 5,
                    time_spent: 6
                )
            ],
            $tasks[1]['id'] => [
                TestTask::createSub(
                    status: 1,
                    time_estimated: 2,
                    time_spent: 1
                ),
                TestTask::createSub(
                    status: 2,
                    time_estimated: 3,
                    time_spent: 3
                ),
            ],
        ];
        $ttp->initTasksAndTimes($tasks, $subtasks_by_task_ids);

        // total times
        $this->assertSame(
            10.0,
            $ttp->getEstimatedTotal(),
            'TaskTimesPreparer->getEstimatedTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            10.0,
            $ttp->getSpentTotal(),
            'TaskTimesPreparer->getSpentTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            1.0,
            $ttp->getRemainingTotal(),
            'TaskTimesPreparer->getRemainingTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            1.0,
            $ttp->getOvertimeTotal(),
            'TaskTimesPreparer->getOvertimeTotal() returned wrong value with subtasks.'
        );
    }

    public function testSubtasksTimesC()
    {
        $ttp = new TasksTimesPreparer();

        $tasks = [
            TestTask::create(),
            TestTask::create()
        ];
        $subtasks_by_task_ids = [
            $tasks[0]['id'] => [
                TestTask::createSub(
                    status: 1,
                    time_estimated: 5,
                    time_spent: 6
                )
            ],
            $tasks[1]['id'] => [
                TestTask::createSub(
                    status: 2,
                    time_estimated: 2,
                    time_spent: 0.5
                ),
                TestTask::createSub(
                    status: 2,
                    time_estimated: 3,
                    time_spent: 3
                ),
            ],
        ];
        $ttp->initTasksAndTimes($tasks, $subtasks_by_task_ids);

        // total times
        $this->assertSame(
            10.0,
            $ttp->getEstimatedTotal(),
            'TaskTimesPreparer->getEstimatedTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            9.5,
            $ttp->getSpentTotal(),
            'TaskTimesPreparer->getSpentTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            0.0,
            $ttp->getRemainingTotal(),
            'TaskTimesPreparer->getRemainingTotal() returned wrong value with subtasks.'
        );
        $this->assertSame(
            -0.5,
            $ttp->getOvertimeTotal(),
            'TaskTimesPreparer->getOvertimeTotal() returned wrong value with subtasks.'
        );
    }

    public function testFloatToHHMMWrapper()
    {
        // this basically also will test TimesData::floatHHMM()
        $msg = 'TasksTimesPreparer->floatToHHMM() and probably TimesData::floatToHHMM() output is wrong.';
        $ttp = new TasksTimesPreparer();
        $this->assertSame('0:14', $ttp->floatToHHMM(0.234765), $msg);
        $this->assertSame('0:14', $ttp->floatToHHMM(0.235), $msg);
        $this->assertSame('0:14', $ttp->floatToHHMM(0.249), $msg);
        $this->assertSame('0:15', $ttp->floatToHHMM(0.25), $msg);
        $this->assertSame('12:48', $ttp->floatToHHMM(12.80), $msg);
    }

    public function testTimesPerColumns()
    {
        $ttp = new TasksTimesPreparer();

        $tasks = [
            TestTask::create(column_name: 'backlog', time_estimated: 10.0),
            TestTask::create(column_name: 'backlog', time_estimated: 2.0),
            TestTask::create(column_name: 'started', time_estimated: 5.0),
            TestTask::create(column_name: 'started', time_estimated: 2.0),
        ];
        $ttp->initTasksAndTimes($tasks);
        $msg = 'TasksTimesPreparer times column calculation went wrong.';
        $this->assertSame(12.0, $ttp->getEstimatedByColumn('backlog'), $msg);
        $this->assertSame(7.0, $ttp->getEstimatedByColumn('started'), $msg);
    }

    public function testTimesPerTask()
    {
        $ttp = new TasksTimesPreparer();

        $tasks = [
            TestTask::create(time_estimated: 10.0, time_spent: 8.5),
            TestTask::create(time_estimated: 2.0, time_spent: 3.0),
            TestTask::create(time_estimated: 5.0, time_spent: 1.0, time_remaining: 0.0),
        ];
        $subtasks = [
            $tasks[2]['id'] => [
                TestTask::createSub(2, $tasks[2]['id'], 5.0, 0.0, 1.0)
            ]
        ];
        $ttp->initTasksAndTimes($tasks, $subtasks);
        $msg = 'TasksTimesPreparer times per task went wrong.';
        $this->assertSame(1.5, $ttp->getRemainingByTask($tasks[0]['id']), $msg);
        $this->assertSame(1.0, $ttp->getOvertimeByTask($tasks[1]['id']), $msg);
        $this->assertSame(-4.0, $ttp->getOvertimeByTask($tasks[2]['id']), $msg);
    }

    public function testTimesPerLevel()
    {
        $config = [
            'levels_config' => [
                'level_1' => 'col_a',
                'level_2' => 'col_b',
                'level_3' => 'col_a [swim_a]',
                'level_4' => '[swim_b]',
                'level_5' => '',
            ]
        ];
        $ttp = new TasksTimesPreparer($config);

        $tasks = [
            TestTask::create(time_estimated: 10.0, column_name: 'col_a', swimlane_name: ''),
            TestTask::create(time_estimated: 2.0, column_name: 'col_a', swimlane_name: 'swim_a'),
            TestTask::create(time_estimated: 5.0, column_name: 'col_b', swimlane_name: ''),
            TestTask::create(time_estimated: 4.0, column_name: 'col_b', swimlane_name: 'swim_b'),
            TestTask::create(time_estimated: 3.0, column_name: 'col_c', swimlane_name: 'swim_b'),
        ];

        $ttp->initTasksAndTimes($tasks);
        $msg = 'TasksTimesPreparer times per level went wrong.';
        $this->assertSame(12.0, $ttp->getEstimatedByLevel('level_1'), $msg);
        $this->assertSame(9.0, $ttp->getEstimatedByLevel('level_2'), $msg);
        $this->assertSame(2.0, $ttp->getEstimatedByLevel('level_3'), $msg);
        $this->assertSame(7.0, $ttp->getEstimatedByLevel('level_4'), $msg);
        $this->assertSame(0.0, $ttp->getEstimatedByLevel('level_5'), $msg);
    }
}
