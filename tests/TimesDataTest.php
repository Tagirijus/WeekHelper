<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TimesData.php';


use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TimesData;


final class TimesDataTest extends TestCase
{
    public function testPercentSimple()
    {
        $msg = 'TimesData percentage methods do not act like intended.';

        $td = new TimesData();

        $td->addTimes(10.0, 4.5, 5.5, 0.0);
        $this->assertSame(0.45, $td->getPercent(), $msg);
        $this->assertSame('45%', $td->getPercentAsString(), $msg);

        $td->addTimes(2.0, 1.0, 1.0, 0.0);
        $this->assertSame(5.5 / 12.0, $td->getPercent(), $msg);
        $this->assertSame('46%', $td->getPercentAsString(), $msg);
    }

    public function testPercentOvertime()
    {
        $msg = 'TimesData percentage methods do not act like intended with overtime.';

        $td = new TimesData();
        // this happens, if I am done faster than estimated;
        // 0.0 remaining should always be considered 100%
        $td->addTimes(10.0, 5.0, 0.0, -5.0);
        $this->assertSame(1.0, $td->getPercent(), $msg);
        $this->assertSame('100%', $td->getPercentAsString(), $msg);

        $td = new TimesData();
        $td->addTimes(10.0, 15.0, 0.0, 5.0);
        $td->addTimes(2.0, 0.0, 2.0, 0.0);
        // makes 12 estimated, 15 spent, 2 remaining and 5 overtime;
        // can happen if these times come from subtasks and I have
        // overtime for one task, and another still open to do subtask
        $this->assertSame(15.0 / 17.0, $td->getPercent(), $msg);
        $this->assertSame('88%', $td->getPercentAsString(), $msg);

        $td = new TimesData();
        $td->addTimes(10.0, 5.0, 0.0, -5.0);
        $td->addTimes(2.0, 0.0, 2.0, 0.0);
        // makes 12 estimated, 5 spent, 2 remaining and -5 overtime;
        // can happen if these times come from subtasks and I have
        // negative overtime for one task, because I was faster
        // and another still open to do subtask
        $this->assertSame(5.0 / 7.0, $td->getPercent(), $msg);
        $this->assertSame('71%', $td->getPercentAsString(), $msg);

        $td = new TimesData();
        $td->addTimes(10.0, 10.0, 0.0, 0.0);
        $td->addTimes(2.0, 20.0, 0.0, 18.0);
        $this->assertSame(1.0, $td->getPercent(), $msg);
        $this->assertSame('100%', $td->getPercentAsString(), $msg);

        $td = new TimesData();
        $td->addTimes(2.0, 1.0, 1.0, 0.0);
        $td->addTimes(2.0, 20.0, 0.0, 18.0);
        $this->assertSame(21.0 / 22.0, $td->getPercent(), $msg);
        $this->assertSame('95%', $td->getPercentAsString(), $msg);
    }
}
