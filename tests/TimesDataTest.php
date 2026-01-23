<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TimesData.php';


use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TimesData;


final class TimesDataTest extends TestCase
{
    public function testPercent()
    {
        $msg = 'TimesData percentage methods do not act like intended.';

        $td = new TimesData();

        $td->addTimes(10.0, 4.5, 5.5, 0.0);
        $this->assertSame(0.45, $td->getPercent(), $msg);
        $this->assertSame('45%', $td->getPercentAsString(), $msg);

        $td->addTimes(2.0, 1.0, 0.0, -1.0);
        $this->assertSame(5.5 / 12.0, $td->getPercent(), $msg);
        $this->assertSame('46%', $td->getPercentAsString(), $msg);
    }
}
