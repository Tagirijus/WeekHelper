<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/ProjectConditions.php';
require_once __DIR__ . '/../Helper/TimeHelper.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\ProjectConditions;


final class ProjectConditionsTest extends TestCase
{

    public function testProjectConditionsAddAndGet()
    {
        $project_conditions = new ProjectConditions();

        $this->assertSame(
            0,
            $project_conditions->getPlannedTimeForDay(0, 'mon'),
            'ProjectConditions test project_1_init should have no minutes for Monday.'
        );
        $project_conditions->addTimeToDay(0, 'mon', 30);
        $project_conditions->addTimeToDay(0, 'mon', 30);
        $this->assertSame(
            60,
            $project_conditions->getPlannedTimeForDay(0, 'mon'),
            'ProjectConditions test project "0" should now have minutes for Monday.'
        );
        $project_conditions->addTimeToDay(0, 'fri', 90);
        $this->assertSame(
            90,
            $project_conditions->getPlannedTimeForDay(0, 'fri'),
            'ProjectConditions test project "0" should now have 90 minutes for Friday.'
        );

        $this->assertSame(
            0,
            $project_conditions->getPlannedTimeForDay(1, 'mon'),
            'ProjectConditions test project "1" should still have 0 minutes for Monday.'
        );
        $project_conditions->addTimeToDay(1, 'tue', 75);
        $this->assertSame(
            75,
            $project_conditions->getPlannedTimeForDay(1, 'tue'),
            'ProjectConditions test project "1" should now have 75 minutes for Tuesday.'
        );
    }

    public function testProjectConditionsTaskCheck()
    {
        $project_conditions = new ProjectConditions();
        $task = ['project_id' => 4, 'project_max_hours_day' => 3];

        // should make 120 minutes left for this day
        $project_conditions->addTimeToDay(4, 'mon', 60);
        // should make 10 minutes left for this day
        $project_conditions->addTimeToDay(4, 'tue', 170);

        $this->assertSame(
            120,
            $project_conditions->getLeftDailyTime($task, 'mon'),
            'Task should have 120 minutes left on Monday.'
        );
        $this->assertSame(
            10,
            $project_conditions->getLeftDailyTime($task, 'tue'),
            'Task should have 120 minutes left on Tuesday.'
        );
    }
}
