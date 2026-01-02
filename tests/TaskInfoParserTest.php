<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TaskInfoParser.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TaskInfoParser;


final class TaskInfoParserTest extends TestCase
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
        TaskInfoParser::extendTask($task_overwrites);
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
        TaskInfoParser::extendTask($task_left_type);
        $this->assertSame(
            'office',
            $task_left_type['project_type'],
            'Task info was not parsed correctly.'
        );

        $this->assertFalse(
            array_key_exists('project_type', $task_new_set),
            '$task_new_set should not have the project_type key yet.'
        );
        TaskInfoParser::extendTask($task_new_set);
        $this->assertSame(
            'studio',
            $task_new_set['project_type'],
            'Task info was not parsed correctly.'
        );
    }
}
