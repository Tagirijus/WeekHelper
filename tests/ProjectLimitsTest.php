<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/ProjectLimits.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\ProjectLimits;


final class ProjectLimitsTest extends TestCase
{
    public function testInit()
    {
        $pl = new ProjectLimits([
            'project_max_hours_wed' => 2.5
        ]);
        $msg = 'ProjectLimit got not initialized correctly.';
        $this->assertSame(
            1440,
            $pl->getLimit('mon'),
            $msg
        );
        $this->assertSame(
            150,
            $pl->getLimit('wed'),
            $msg
        );
        $this->assertSame(
            -1,
            $pl->getLimit('does not exist'),
            $msg
        );
    }

    public function testSubstraction()
    {
        $pl = new ProjectLimits([
            'project_max_hours_day' => 2
        ]);
        $pl->substractLimit('mon', 60);
        $pl->substractLimit('tue', 30);
        $diff = $pl->substractLimit('thu', 130);
        $msg = 'ProjectLimit subtractino of limits did not work as intended.';
        $this->assertSame(
            60,
            $pl->getLimit('mon'),
            $msg
        );
        $this->assertSame(
            90,
            $pl->getLimit('tue'),
            $msg
        );
        $this->assertSame(
            120,
            $pl->getLimit('wed'),
            $msg
        );
        $this->assertSame(
            0,
            $pl->getLimit('thu'),
            $msg
        );
        $this->assertSame(
            10,
            $diff,
            $msg
        );
    }
}
