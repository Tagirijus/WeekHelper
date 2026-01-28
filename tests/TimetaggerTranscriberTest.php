<?php

declare(strict_types=1);

namespace Kanboard\Plugin\WeekHelper\tests;

require_once __DIR__ . '/../Helper/TimeHelper.php';
require_once __DIR__ . '/../Model/TimetaggerEvent.php';
require_once __DIR__ . '/../Model/TimetaggerFetcher.php';
require_once __DIR__ . '/../Model/TimetaggerTranscriber.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerFetcher;
use Kanboard\Plugin\WeekHelper\Model\TimetaggerTranscriber;


final class TimetaggerTranscriberTest extends TestCase
{
    public function testSpentTimeOverwriting()
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
                'time_spent' => 0.0,  # should become 0.5
                'time_estimated' => 0.5,
                'nb_subtasks' => 6,
                'nb_completed_subtasks' => 6,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 2,
                'time_spent' => 0.0,  # should become 2.0
                'time_estimated' => 1.0,
                'nb_subtasks' => 2,
                'nb_completed_subtasks' => 1,
                'timetagger_tags' => 'a,b,c'
            ],
            [
                'id' => 3,
                'time_spent' => 9.9,  # should stay 9.9
                'time_estimated' => 10,
                'nb_subtasks' => 0,
                'nb_completed_subtasks' => 0,
            ],
            [
                'id' => 4,
                'time_spent' => 1.0,  # should become 2.5
                'time_estimated' => 3.0,
                'nb_subtasks' => 0,
                'nb_completed_subtasks' => 0,
                'timetagger_tags' => 'e'
            ],
            [
                'id' => 5,
                'time_spent' => 1.0,  # should become 2.75
                'time_estimated' => 0.5,
                'nb_subtasks' => 1,
                'nb_completed_subtasks' => 0,
                'timetagger_tags' => 'g'
            ],
            [
                'id' => 6,
                'time_spent' => 0.0,  # should become 0.25
                'time_estimated' => 0.25,
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
        $this->assertSame(2.0, $tasks[1]['time_spent'], $msg);
        $this->assertSame(9.9, $tasks[2]['time_spent'], $msg);
        $this->assertSame(2.5, $tasks[3]['time_spent'], $msg);
        $this->assertSame(2.75, $tasks[4]['time_spent'], $msg);
        $this->assertSame(0.25, $tasks[5]['time_spent'], $msg);
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
