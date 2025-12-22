<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TimeSpan.php';
require_once __DIR__ . '/../Helper/TimeSlotsDay.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimeSlotsDay;


final class TimeSlotsDayTest extends TestCase
{
    private static string $config;
    private static TimeSlotsDay $time_slots;

    public static function setUpBeforeClass(): void
    {
        self::$config = "6:00-9:00 office\n11:00-13:00 studio\n15:00-17:00";
        self::$time_slots = new TimeSlotsDay(self::$config);
    }

    public function testtimeSlorsDayInitialize(): void
    {
        $this->assertSame(
            0,  # should be the first array key internally
            self::$time_slots->nextSlot('office'),
            'TimeSlotsDay config was parsed incorrectly, assumingly.'
        );
        $this->assertSame(
            1,
            self::$time_slots->nextSlot('studio'),
            'TimeSlotsDay config was parsed incorrectly, assumingly.'
        );
        $this->assertSame(
            900,
            self::$time_slots->getStartOfSlot(2),
            'TimeSlotsDay config was parsed incorrectly, assumingly.'
        );
    }

    public function testTimeSlotsDayMethods()
    {
        // plan time (30 min) on second slot
        $slot_key = self::$time_slots->nextSlot('studio');
        self::$time_slots->planTime($slot_key, 30);
        $this->assertSame(
            690,  # should be 11:30 o'clock
            self::$time_slots->getStartOfSlot($slot_key),
            'Was not able to plan time in TimeSlotsDay for the second time slot.'
        );

        // now check the length of the first slot, which should still be
        // 3 hours / 180 minutes
        $this->assertSame(
            180,
            self::$time_slots->getLengthOfSlot(0),
            'First time slot is not 3 horus / 180 minutes long.'
        );

        // then "deplete" the firs slot completely so that the next
        // available free slot time should be on the second slot
        // at 11:30 o'clock / 690 minutes
        // this should also check at the same time that without
        // a given project type any project type is legit, since
        // the next free slot is actually of type "studio", but
        // in nextSlot() not type is defined.
        $this->assertTrue(
            self::$time_slots->depleteSlot(0),
            'Was not able to deplete the first slot.'
        );
        $slot_key = self::$time_slots->nextSlot();
        $this->assertSame(
            690,
            self::$time_slots->getStartOfSlot($slot_key),
            '11:30 (690 minutes) was not feteched as next start. Either depletion or type fetching failed.'
        );
    }
}
