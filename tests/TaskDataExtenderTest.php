<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TaskDataExtender.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TaskDataExtender;


final class TaskDataExtenderTest extends TestCase
{
    public function testProjectTypeOverwriting()
    {
        $task_overwrites = [
            'project_type' => 'office',
            'description' => (
                "any other descriptions text\nis here\nin multiline\n"
                . "project_type=\n"
            )
        ];
        $task_left_type = [
            'project_type' => 'office',
            'description' => (
                "any other descriptions text\nis here\nin multiline\n"
                . "anything_else=nothing\n"
            )
        ];
        $task_new_set = [
            'description' => (
                "any other descriptions text\nis here\nin multiline\n"
                . "project_type=studio\n"
            )
        ];

        $this->assertSame(
            'office',
            $task_overwrites['project_type'],
            'Task info was not parsed correctly.'
        );
        TaskDataExtender::extendTask($task_overwrites);
        $this->assertSame(
            '',
            $task_overwrites['project_type'],
            'Task info was not parsed correctly.'
        );

        $this->assertSame(
            'office',
            $task_left_type['project_type'],
            'Task info was not parsed correctly.'
        );
        TaskDataExtender::extendTask($task_left_type);
        $this->assertSame(
            'office',
            $task_left_type['project_type'],
            'Task info was not parsed correctly.'
        );

        $this->assertFalse(
            array_key_exists('project_type', $task_new_set),
            '$task_new_set should not have the project_type key yet.'
        );
        TaskDataExtender::extendTask($task_new_set);
        $this->assertSame(
            'studio',
            $task_new_set['project_type'],
            'Task info was not parsed correctly.'
        );
    }

    public function testTaskEarliestStart()
    {
        $task = [
            'description' => (
                "any other descriptions text\nis here\nin multiline\n"
                . "plan_from=wed 10:00\n"
            )
        ];

        $this->assertFalse(
            array_key_exists('plan_from', $task),
            '$task should not have the plan_from key yet.'
        );
        TaskDataExtender::extendTask($task);
        $this->assertSame(
            'wed 10:00',
            $task['plan_from'],
            'Task info was not parsed correctly.'
        );
    }

    public function testTimetaggerValue()
    {
        $task = [
            'description' => (
                "any other descriptions text\nis here\nin multiline\n"
                . "timetagger_tags=client,project,task\n"
            )
        ];

        $this->assertFalse(
            array_key_exists('timetagger_tags', $task),
            '$task should not have the timetagger_tags key yet.'
        );
        TaskDataExtender::extendTask($task);
        $this->assertSame(
            'client,project,task',
            $task['timetagger_tags'],
            'Task info was not parsed correctly.'
        );
    }

    public function testInfoParserState()
    {
        $task = [
            'description' => (
                "any other descriptions text\nis here\nin multiline\n"
                . "timetagger_tags=client,project,task\n"
            )
        ];
        $this->assertFalse(
            isset($task['info_parsed']),
            'TaskDataExtender was parsed already?'
        );
        TaskDataExtender::extendTask($task);
        $this->assertTrue(
            isset($task['info_parsed']),
            'TaskDataExtender should have a "i was parsed" state now!'
        );
    }

    public function testLevelsExtend()
    {
        $task = [
            'description' => '',
            'swimlane_name' => 'lane_a',
            'column_name' => 'col_a'
        ];
        $levels_config = [
            'level_1' => '',
            'level_2' => 'col_b, col_a',
            'level_3' => 'col_a [lane_a], col_c',
            'level_4' => 'col_c [lane_a]',
            'level_5' => '[lane_a], col_b'
        ];
        $expected_levels = [
            'level_2', 'level_3', 'level_5'
        ];
        $this->assertFalse(
            isset($task['levels']),
            'TaskDataExtender error during levels parsing.'
        );
        TaskDataExtender::extendTask($task, $levels_config);
        $this->assertTrue(
            isset($task['levels']),
            'TaskDataExtender error during levels parsing.'
        );
        $this->assertSame(
            $expected_levels,
            $task['levels'],
            'TaskDataExtender error during levels parsing.'
        );
    }
}
