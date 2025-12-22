<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/ProjectInfoParser.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\ProjectInfoParser;


final class ProjectInfoParserTest extends TestCase
{
    public function testProjectInfoParserParsing()
    {
        $project = [
            'description' => (
                "project_priority=2\n"
                . "project_type=office\n"
                . "project_max_hours_day=3\n"
                . "project_max_hours_block=1\n"
                . "project_wage=30\n"
                . "project_alias=debug\n"
            )
        ];
        $parsed_data = [
            'project_priority' => 2,
            'project_type' => 'office',
            'project_max_hours_day' => 3.0,
            'project_max_hours_block' => 1.0,
            'project_wage' => 30.0,
            'project_alias' => 'debug',
        ];
        $this->assertSame(
            $parsed_data,
            ProjectInfoParser::getProjectInfoByProject($project),
            'Project info was not parsed correctly.'
        );
    }
}
