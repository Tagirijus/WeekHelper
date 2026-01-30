<?php

declare(strict_types=1);

namespace Kanboard\Plugin\WeekHelper\tests;

require_once __DIR__ . '/../Helper/TimeHelper.php';
require_once __DIR__ . '/../Model/TimesCalculator.php';
require_once __DIR__ . '/../Model/TimetaggerEvent.php';
require_once __DIR__ . '/../Model/TimetaggerFetcher.php';
require_once __DIR__ . '/../Model/TimetaggerTranscriber.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerFetcher;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerTranscriber;


final class TimetaggerTranscriberTest extends TestCase
{
    /**
     * Here I test some general overwriting things and checking the
     * overwritten times.
     */
    public function testTimesOverwriting()
    {
        //
        // SIMULATED TIMETAGGER FETCHING
        //

        $tf = new TimetaggerFetcher();
        $json = '{"records": [';
        // length: 1.5 hours
        // tags: a, b, c
        $json .= '{"key": "A", "mt": 0, "t1": 0, "t2": 5400, "ds": "#a #b #c", "st": 0.0},';
        // length: 1 hours
        // tags: a, b, c, d
        $json .= '{"key": "B", "mt": 0, "t1": 5400, "t2": 9000, "ds": "#a #b #c #d", "st": 0.0},';
        // length: 2.5 hours
        // tags: e, f
        $json .= '{"key": "C", "mt": 0, "t1": 9000, "t2": 18000, "ds": "#e #f", "st": 0.0},';
        // length: 3.0 hours
        // tags: g
        $json .= '{"key": "D", "mt": 0, "t1": 20000, "t2": 30800, "ds": "#g", "st": 0.0}';
        $json .= ']}';

        $tf->events = TimetaggerFetcher::eventsFromJSONString($json);


        //
        // SIMULATED KANBAORD TASKS
        //
        $tasks = [
            [
                'id' => 1,
                'time_estimated' => 0.5,
                'time_spent' => 0.0,      # should become 0.5
                'time_remaining' => 0.5,  # should become 0.0
                'time_overtime' => -0.5,  # should become 0.0
                'nb_subtasks' => 6,
                'nb_completed_subtasks' => 6,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 2,
                'time_estimated' => 1.0,
                'time_spent' => 0.0,      # should become 2.0
                'time_remaining' => 1.0,  # should become 0.0
                'time_overtime' => 0.0,   # should become 1.0
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 3,
                'time_estimated' => 10,
                'time_spent' => 9.9,      # should stay 9.9
                'time_remaining' => 0.1,  # should stay 0.1
                'time_overtime' => 0.0,   # should stay 0.0
                'nb_subtasks' => 0,
                'nb_completed_subtasks' => 0,
            ],
            [
                'id' => 4,
                'time_estimated' => 3.0,
                'time_spent' => 1.0,      # should become 2.5
                'time_remaining' => 2.0,  # should become 0.5
                'time_overtime' => 0.0,   # should stay 0.0
                'nb_subtasks' => 0,
                'nb_completed_subtasks' => 0,
                'timetagger_tags' => 'e'
            ],
            [
                'id' => 5,
                'time_estimated' => 0.5,
                'time_spent' => 1.0,      # should become 2.75
                'time_remaining' => 0.0,  # should stay 0.0
                'time_overtime' => 0.5,   # should become 2.25
                'nb_subtasks' => 1,
                'nb_completed_subtasks' => 0,
                'timetagger_tags' => 'g'
            ],
            [
                'id' => 6,
                'time_estimated' => 0.25,
                'time_spent' => 0.0,      # should become 0.25
                'time_remaining' => 0.25, # should become 0.0
                'time_overtime' => 0.0,   # should stay 0.0
                'nb_subtasks' => 1,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'g'
            ]
        ];


        // now overwrite these tasks spent times
        $ts = new TimetaggerTranscriber($tf);
        foreach ($tasks as &$task) {
            $ts->overwriteTimesForTask($task);
        }
        $ts->overwriteTimesForRemainingTasks();

        $msg = 'TimetaggerTranscriber incorrectly modified the spent times for the tasks.';
        // final check
        $this->assertSame(0.5, $tasks[0]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[0]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[0]['time_overtime'], $msg);

        $this->assertSame(2.0, $tasks[1]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[1]['time_remaining'], $msg);
        $this->assertSame(1.0, $tasks[1]['time_overtime'], $msg);

        $this->assertSame(9.9, $tasks[2]['time_spent'], $msg);
        $this->assertSame(0.1, $tasks[2]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[2]['time_overtime'], $msg);

        $this->assertSame(2.5, $tasks[3]['time_spent'], $msg);
        $this->assertSame(0.5, $tasks[3]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[3]['time_overtime'], $msg);

        $this->assertSame(2.75, $tasks[4]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[4]['time_remaining'], $msg);
        $this->assertSame(2.25, $tasks[4]['time_overtime'], $msg);

        $this->assertSame(0.25, $tasks[5]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[5]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[5]['time_overtime'], $msg);
    }

    /**
     * This case tests, what will happen, if a task is open
     * and will get more spent time than estimated. Overtime
     * should be changed as well.
     */
    public function testTimesOverwritingB()
    {
        //
        // SIMULATED TIMETAGGER FETCHING
        //

        $tf = new TimetaggerFetcher();
        $json = '{"records": [';
        // length: 5.25 hours
        // tags: a, b, c
        $json .= '{"key": "A", "mt": 0, "t1": 0, "t2": 18900, "ds": "#a #b #c", "st": 0.0}';
        $json .= ']}';
        // finally this makes for project "#a #b #c":
        // length: 5.25

        $tf->events = TimetaggerFetcher::eventsFromJSONString($json);


        //
        // SIMULATED KANBAORD TASKS
        //
        $tasks = [
            [
                'id' => 1,
                'time_estimated' => 2.0,
                'time_spent' => 0.0,      # should become 5.25
                'time_remaining' => 2.0,  # should become 0.0
                'time_overtime' => 0.0,   # should become 3.25
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'a,b,c'
            ],
        ];


        // now overwrite these tasks spent times
        $ts = new TimetaggerTranscriber($tf);
        foreach ($tasks as &$task) {
            $ts->overwriteTimesForTask($task);
        }
        $ts->overwriteTimesForRemainingTasks();

        $msg = 'TimetaggerTranscriber incorrectly modified the spent times for the tasks, case B.';
        // final check
        $this->assertSame(5.25, $tasks[0]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[0]['time_remaining'], $msg);
        $this->assertSame(3.25, $tasks[0]['time_overtime'], $msg);
    }

    /**
     * This case is similar to case B, while now the task is
     * done. It should still get the correct amount of spent
     * time and also the overtime accordingly as well again.
     */
    public function testTimesOverwritingC()
    {
        //
        // SIMULATED TIMETAGGER FETCHING
        //

        $tf = new TimetaggerFetcher();
        $json = '{"records": [';
        // length: 5.25 hours
        // tags: a, b, c
        $json .= '{"key": "A", "mt": 0, "t1": 0, "t2": 18900, "ds": "#a #b #c", "st": 0.0}';
        $json .= ']}';
        // finally this makes for project "#a #b #c":
        // length: 5.25

        $tf->events = TimetaggerFetcher::eventsFromJSONString($json);


        //
        // SIMULATED KANBAORD TASKS
        //
        $tasks = [
            [
                'id' => 1,
                'time_estimated' => 2.0,
                'time_spent' => 0.0,      # should become 5.25
                'time_remaining' => 2.0,  # should become 0.0
                'time_overtime' => 0.0,   # should become 3.25
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 2, # <-- this changed in case C; task is done now!
                'timetagger_tags' => 'a,b,c'
            ],
        ];


        // now overwrite these tasks spent times
        $ts = new TimetaggerTranscriber($tf);
        foreach ($tasks as &$task) {
            $ts->overwriteTimesForTask($task);
        }
        $ts->overwriteTimesForRemainingTasks();

        $msg = 'TimetaggerTranscriber incorrectly modified the spent times for the tasks, case C.';
        // final check
        $this->assertSame(5.25, $tasks[0]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[0]['time_remaining'], $msg);
        $this->assertSame(3.25, $tasks[0]['time_overtime'], $msg);
    }

    /**
     * This test is about two tasks sharing the same timetagger tags,
     * while being open both. Only the first one in the cue should get
     * the spent time filled up.
     */
    public function testTimesOverwritingD()
    {
        //
        // SIMULATED TIMETAGGER FETCHING
        //

        $tf = new TimetaggerFetcher();
        $json = '{"records": [';
        // length: 5.25 hours
        // tags: a, b, c
        $json .= '{"key": "A", "mt": 0, "t1": 0, "t2": 18900, "ds": "#a #b #c", "st": 0.0}';
        $json .= ']}';
        // finally this makes for project "#a #b #c":
        // length: 5.25

        $tf->events = TimetaggerFetcher::eventsFromJSONString($json);


        //
        // SIMULATED KANBAORD TASKS
        //
        $tasks = [
            [
                'id' => 1,
                'time_estimated' => 2.0,
                'time_spent' => 0.0,      # should become 5.25
                'time_remaining' => 2.0,  # should become 0.0
                'time_overtime' => 0.0,   # should become 3.25
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 2,
                'time_estimated' => 1.5,
                'time_spent' => 0.0,      # should stay
                'time_remaining' => 1.5,  # should stay
                'time_overtime' => 0.0,   # should stay
                'nb_subtasks' => 1,
                'nb_completed_subtasks' => 0,
                'timetagger_tags' => 'a,b,c'
            ],
        ];


        // now overwrite these tasks spent times
        $ts = new TimetaggerTranscriber($tf);
        foreach ($tasks as &$task) {
            $ts->overwriteTimesForTask($task);
        }
        $ts->overwriteTimesForRemainingTasks();

        $msg = 'TimetaggerTranscriber incorrectly modified the spent times for the tasks, case D.';
        // final check
        $this->assertSame(5.25, $tasks[0]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[0]['time_remaining'], $msg);
        $this->assertSame(3.25, $tasks[0]['time_overtime'], $msg);

        $this->assertSame(0.0, $tasks[1]['time_spent'], $msg);
        $this->assertSame(1.5, $tasks[1]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[1]['time_overtime'], $msg);
    }

    /**
     * This test is about two tasks sharing the same timetagger tags
     * again. This time first one is open, getting all the time. The
     * second one has already done subtasks, though, but still open,
     * and should still be at 0, because the first open task should
     * get all the time already.
     */
    public function testTimesOverwritingE()
    {
        //
        // SIMULATED TIMETAGGER FETCHING
        //

        $tf = new TimetaggerFetcher();
        $json = '{"records": [';
        // length: 5.25 hours
        // tags: a, b, c
        $json .= '{"key": "A", "mt": 0, "t1": 0, "t2": 18900, "ds": "#a #b #c", "st": 0.0}';
        $json .= ']}';
        // finally this makes for project "#a #b #c":
        // length: 5.25

        $tf->events = TimetaggerFetcher::eventsFromJSONString($json);


        //
        // SIMULATED KANBAORD TASKS
        //
        $tasks = [
            [
                'id' => 1,
                'time_estimated' => 1.5,
                'time_spent' => 0.0,      # should become 5.25
                'time_remaining' => 1.5,  # should become 0.0
                'time_overtime' => 0.0,   # should become 3.75
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 2,
                'time_estimated' => 2.0,
                'time_spent' => 1.0,      # should become 0.0
                'time_remaining' => 1.0,  # should become 2.0
                'time_overtime' => 0.0,   # should stay
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 3,
                'time_estimated' => 3.0,
                'time_spent' => 1.5,      # should become 0.0
                'time_remaining' => 1.5,  # should become 3.0
                'time_overtime' => 0.0,   # should stay
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'doesNotExist'
            ],
        ];


        // now overwrite these tasks spent times
        $ts = new TimetaggerTranscriber($tf);
        foreach ($tasks as &$task) {
            $ts->overwriteTimesForTask($task);
        }
        $ts->overwriteTimesForRemainingTasks();

        $msg = 'TimetaggerTranscriber incorrectly modified the spent times for the tasks, case E.';
        // final check
        $this->assertSame(5.25, $tasks[0]['time_spent'], $msg);
        $this->assertSame(0.0, $tasks[0]['time_remaining'], $msg);
        $this->assertSame(3.75, $tasks[0]['time_overtime'], $msg);

        $this->assertSame(0.0, $tasks[1]['time_spent'], $msg);
        $this->assertSame(2.0, $tasks[1]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[1]['time_overtime'], $msg);

        $this->assertSame(0.0, $tasks[2]['time_spent'], $msg);
        $this->assertSame(3.0, $tasks[2]['time_remaining'], $msg);
        $this->assertSame(0.0, $tasks[2]['time_overtime'], $msg);
    }

    public function testTagsMatch()
    {
        $task_tags = 'kanboard-todo,code';
        $tags_a = [
            'kanboard-todo',
            'code',
            'additional'
        ];
        $tags_b = [
            'code',
            'kanboard-todo'
        ];
        $tags_c = [
            'code',
            'something-else'
        ];
        $this->assertTrue(
            TimetaggerTranscriber::tagsMatch($task_tags, $tags_a),
            'TimetaggerTranscriber::tagsMatch() not working as intended.'
        );
        $this->assertTrue(
            TimetaggerTranscriber::tagsMatch($task_tags, $tags_b),
            'TimetaggerTranscriber::tagsMatch() not working as intended.'
        );
        $this->assertFalse(
            TimetaggerTranscriber::tagsMatch($task_tags, $tags_c),
            'TimetaggerTranscriber::tagsMatch() not working as intended.'
        );

        $task_tags = '';
        $tags_a = ['anything'];
        $this->assertFalse(
            TimetaggerTranscriber::tagsMatch($task_tags, $tags_a),
            'TimetaggerTranscriber::tagsMatch() not working as intended.'
        );
    }

    public function testTimetaggerTagSorting()
    {
        $msg = 'TimetaggerTranscriber::getTimetaggerTagsSorted() not working as intended.';
        $this->assertSame(
            'a,b,c',
            TimetaggerTranscriber::getTimetaggerTagsSorted('b,c,a'),
            $msg
        );
        $this->assertSame(
            '',
            TimetaggerTranscriber::getTimetaggerTagsSorted(''),
            $msg
        );
        $this->assertSame(
            '0,2,a,b,c',
            TimetaggerTranscriber::getTimetaggerTagsSorted('b,0,c,a,2'),
            $msg
        );
    }
}
