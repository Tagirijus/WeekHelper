<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/ProjectQuota.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\ProjectQuota;


final class ProjectQuotaTest extends TestCase
{
    public function testInit()
    {
        $pl = new ProjectQuota([
            'project_max_hours_wed' => 2.5
        ]);
        $msg = 'ProjectQuota got not initialized correctly.';
        $this->assertSame(
            1440,
            $pl->getQuota('mon'),
            $msg
        );
        $this->assertSame(
            150,
            $pl->getQuota('wed'),
            $msg
        );
        $this->assertSame(
            -1,
            $pl->getQuota('does not exist'),
            $msg
        );
    }

    public function testInit2()
    {
        $pl = new ProjectQuota([
            'project_max_hours_day' => 2,
            'project_max_hours_mon' => -1
        ]);
        $msg = 'ProjectQuota got not initialized correctly.';
        $this->assertSame(
            120,
            $pl->getQuota('mon'),
            $msg
        );
    }

    public function testSubstraction()
    {
        $pl = new ProjectQuota([
            'project_max_hours_day' => 2
        ]);
        $pl->substractQuota('mon', 60);
        $pl->substractQuota('tue', 30);
        $diff = $pl->substractQuota('thu', 130);
        $msg = 'ProjectQuota subtractino of quota did not work as intended.';
        $this->assertSame(
            60,
            $pl->getQuota('mon'),
            $msg
        );
        $this->assertSame(
            90,
            $pl->getQuota('tue'),
            $msg
        );
        $this->assertSame(
            120,
            $pl->getQuota('wed'),
            $msg
        );
        $this->assertSame(
            0,
            $pl->getQuota('thu'),
            $msg
        );
        $this->assertSame(
            10,
            $diff,
            $msg
        );
    }
}
