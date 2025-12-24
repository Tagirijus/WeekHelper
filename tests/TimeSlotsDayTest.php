<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/TimeHelper.php';
require_once __DIR__ . '/../Helper/TimeSpan.php';
require_once __DIR__ . '/../Helper/TimePoint.php';
require_once __DIR__ . '/../Helper/TimeSlotsDay.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
use Kanboard\Plugin\WeekHelper\Helper\TimeSpan;
use Kanboard\Plugin\WeekHelper\Helper\TimePoint;
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

    public function testTimeSlotsDayInitialize(): void
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

        // now try to plan a project type, which does not exist,
        // thus should be planned on the last slot, which has no
        // type restrictions attached
        $this->assertSame(
            2,
            self::$time_slots->nextSlot('other_type'),
            'Slot without type restriction could not be fetched.'
        );
    }

    public function testTimeSlotsDayDepleteByTimeSpan()
    {
        //
        // cut something away from START
        //

        $time_slots_day = new TimeSlotsDay("6:00-16:00");
        $time_span = new TimeSpan(
            TimeHelper::readableToMinutes("6:00"),
            TimeHelper::readableToMinutes("10:00"),
        );
        $time_slots_day->depleteByTimeSpan($time_span);
        // there now should still just be one slot, which length
        // shrinked to 6 hours / 360 minutes, starts at 10:00 / 600
        // and still ends at 16:00 / 960
        $this->assertSame(
            360,
            $time_slots_day->getLengthOfSlot(0),
            'Time slot of instance is not 6 hours / 360 min in length.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getStartOfSlot(0),
            'Time slot of instance should start at 10:00 / 600 minutes.'
        );
        $this->assertSame(
            960,
            $time_slots_day->getEndOfSlot(0),
            'Time slot of instance should end at 16:00 / 960 minutes.'
        );


        //
        // cut something away from END
        //

        $time_slots_day = new TimeSlotsDay("6:00-16:00");
        $time_span = new TimeSpan(
            TimeHelper::readableToMinutes("10:00"),
            TimeHelper::readableToMinutes("16:00"),
        );
        $time_slots_day->depleteByTimeSpan($time_span);
        // there now should still just be one slot, which length
        // shrinked to 4 hours / 240 minutes, starts at 6:00 / 360
        // and ends at 10:00 / 600
        $this->assertSame(
            240,
            $time_slots_day->getLengthOfSlot(0),
            'Time slot of instance is not 4 hours / 240 min in length.'
        );
        $this->assertSame(
            360,
            $time_slots_day->getStartOfSlot(0),
            'Time slot of instance should start at 6:00 / 360 minutes.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getEndOfSlot(0),
            'Time slot of instance should end at 10:00 / 600 minutes.'
        );


        //
        // cut something away from MIDDLE
        //

        $time_slots_day = new TimeSlotsDay("6:00-16:00");
        $time_span = new TimeSpan(
            TimeHelper::readableToMinutes("7:00"),
            TimeHelper::readableToMinutes("15:00"),
        );
        $time_slots_day->depleteByTimeSpan($time_span);
        // there now should be two slots:
        // 1: 6:00-7:00 / 360-420
        // 2: 15:00-16:00 / 900-960
        $this->assertSame(
            60,
            $time_slots_day->getLengthOfSlot(0),
            'Time slot 1 of instance is not 1 hours / 60 min in length.'
        );
        $this->assertSame(
            360,
            $time_slots_day->getStartOfSlot(0),
            'Time slot 1 of instance should start at 6:00 / 360 minutes.'
        );
        $this->assertSame(
            420,
            $time_slots_day->getEndOfSlot(0),
            'Time slot 1 of instance should end at 7:00 / 420 minutes.'
        );
        $this->assertSame(
            60,
            $time_slots_day->getLengthOfSlot(1),
            'Time slot 2 of instance is not 1 hours / 60 min in length.'
        );
        $this->assertSame(
            900,
            $time_slots_day->getStartOfSlot(1),
            'Time slot 2 of instance should start at 15:00 / 900 minutes.'
        );
        $this->assertSame(
            960,
            $time_slots_day->getEndOfSlot(1),
            'Time slot 2 of instance should end at 16:00 / 960 minutes.'
        );


        //
        // cut EVERYTHING away
        //

        $time_slots_day = new TimeSlotsDay("6:00-16:00");
        $time_span = new TimeSpan(
            TimeHelper::readableToMinutes("6:00"),
            TimeHelper::readableToMinutes("16:00"),
        );
        $time_slots_day->depleteByTimeSpan($time_span);
        // technically the only available slot should not be depleted
        $this->assertSame(
            0,
            $time_slots_day->getLengthOfSlot(0),
            'Time slot of instance shoudl be depleted.'
        );

        // this shoudl also work, when there are mor slots; then
        // all slots should get depleted
        $time_slots_day = new TimeSlotsDay("6:00-10:00\n15:00-16:00");
        $time_span = new TimeSpan(
            TimeHelper::readableToMinutes("6:00"),
            TimeHelper::readableToMinutes("16:00"),
        );
        $time_slots_day->depleteByTimeSpan($time_span);
        $this->assertSame(
            0,
            $time_slots_day->getLengthOfSlot(0),
            'Time slot 1 of instance shoudl be depleted.'
        );
        $this->assertSame(
            0,
            $time_slots_day->getLengthOfSlot(1),
            'Time slot 2 of instance shoudl be depleted.'
        );
    }

    public function testTimePointChecks()
    {
        $time_slots_day = new TimeSlotsDay("6:00-10:00\n15:00-16:00", 'mon');
        $this->assertSame(
            0,
            $time_slots_day->slotKeyFromTimePoint(new TimePoint('mon 6:00')),
            'Given TimePoint should be in the TimeSlotsDay.'
        );
        $this->assertSame(
            1,
            $time_slots_day->slotKeyFromTimePoint(new TimePoint('mon 15:00')),
            'Given TimePoint should be in the TimeSlotsDay.'
        );
        $this->assertSame(
            -1,
            $time_slots_day->slotKeyFromTimePoint(new TimePoint('tue 6:00')),
            'Given TimePoint should not be in the TimeSlotsDay.'
        );
        $this->assertSame(
            -1,
            $time_slots_day->slotKeyFromTimePoint(new TimePoint('mon 10:01')),
            'Given TimePoint should not be in the TimeSlotsDay.'
        );

        // also check the difference in days
        $time_slots_day = new TimeSlotsDay("6:00-10:00\n15:00-16:00", 'wed');
        $this->assertSame(
            -1,
            $time_slots_day->dayDiffFromTimePoint(new TimePoint('tue 6:00')),
            'Given TimePoint should be -1 days be away from TimeSlotsDay.'
        );
        $this->assertSame(
            2,
            $time_slots_day->dayDiffFromTimePoint(new TimePoint('fri 6:00')),
            'Given TimePoint should be +2 days be away from TimeSlotsDay.'
        );
        $this->assertSame(
            0,
            $time_slots_day->dayDiffFromTimePoint(new TimePoint('wed 19:00')),
            'Given TimePoint should be on the same day as the TimeSlotsDay.'
        );
    }
}
