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

    public function testDepletion()
    {
        $time_slots_day = new TimeSlotsDay("6:00-10:00 office\n15:00-16:00 office\n19:00-20:00 studio", 'mon');
        $this->assertSame(
            2,
            $time_slots_day->nextSlot('studio'),
            'Initially there should be a slot available for this TimeSlotsDay.'
        );
        $time_slots_day->deplete();
        $this->assertSame(
            -1,
            $time_slots_day->nextSlot(),
            'After depletion there should not be any slot available for this TimeSlotsDay.'
        );

        // another depletion test; test depletion by TimePoint
        $time_slots_day = new TimeSlotsDay("6:00-10:00 office\n15:00-16:00 office\n19:00-20:00 studio", 'mon');
        // should not be able to deplete, since the day is incorrect
        $time_slots_day->depleteByTimePoint(new TimePoint('tue 7:00'));
        $next_slot = $time_slots_day->nextSlot();
        $this->assertSame(
            240,
            $time_slots_day->getLengthOfSlot($next_slot),
            'First slot should still have 4 hours / 240 minutes left.'
        );
        // this now should deplet the first slot
        $time_slots_day->depleteByTimePoint(new TimePoint('tue 7:00'), true);
        $next_slot = $time_slots_day->nextSlot();
        $this->assertSame(
            180,
            $time_slots_day->getLengthOfSlot($next_slot),
            'First slot should now only have 3 hours / 180 minutes left.'
        );
        // this should deplete even further
        $time_slots_day->depleteByTimePoint(new TimePoint('mon 19:30'));
        $next_slot = $time_slots_day->nextSlot();
        $this->assertSame(
            2,
            $next_slot,
            'Only the last slot should have available time now.'
        );
        $this->assertSame(
            30,
            $time_slots_day->getLengthOfSlot($next_slot),
            'Last slot should now only have 0.5 hours / 30 minutes left.'
        );
    }

    public function testTimespanInitValues()
    {
        $time_slots_day = new TimeSlotsDay("6:00-10:00 office\n15:00-16:00 office\n19:00-20:00 studio", 'mon');
        $this->assertSame(
            240,
            $time_slots_day->getLengthOfSlot(0),
            'Initially the first time slot should have 4 hours / 240 min.'
        );
        $time_slots_day->depleteSlot(0);
        $this->assertSame(
            0,
            $time_slots_day->getLengthOfSlot(0),
            'After depletion the first time slot should have nothing left.'
        );
        $this->assertSame(
            240,
            $time_slots_day->getLengthOfSlot(0, true),
            'After depletion the first time slot should have nothing left, but with the'
            . ' init_value==true parameter the original value should be returned.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getStartOfSlot(0),
            'After depletion the first time slot should have end as start.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getEndOfSlot(0),
            'After depletion the first time slot should have the same end.'
        );
        $this->assertSame(
            360,
            $time_slots_day->getStartOfSlot(0, true),
            'After depletion the first time slot should have the same start as before,'
            . ' when init_value==true.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getEndOfSlot(0, true),
            'After depletion the first time slot should have the same end,'
            . ' also for init_value - this should not have changed.'
        );
    }

    public function testGetOverallLength()
    {
        $time_slots_day = new TimeSlotsDay("10:00-11:00\n20:00-22:00", 'mon');
        $this->assertSame(
            180,
            $time_slots_day->getLength(),
            'Initially the TimeSlotsDay should have 3 hours / 180 min in total.'
        );
        $time_slots_day->depleteSlot(0);
        $this->assertSame(
            120,
            $time_slots_day->getLength(),
            'After depleting the first slot, there should only be 2 hours / 120 min left.'
        );
        $this->assertSame(
            180,
            $time_slots_day->getLength(true),
            'After depleting the first slot, there should only be 2 hours / 120 min left,'
            . ' but with init_value==true it should look at the init TimeSpan.'
        );
    }

    public function testNextSlotWithEarliestStart()
    {
        // the test for the new nextSlot() method, which now can also
        // get an optional string, representing a "Tasks earliest start"
        // timepoint string. it should be internal an additional check,
        // whether the given timepoint string is ALSO in the one of the
        // next available time slots for this TimeSlotsDay instance. It
        // will return the key of the next slot on success, -1 on fail.
        $time_slots_day = new TimeSlotsDay("10:00-11:00\n20:00-22:00", 'mon');
        $this->assertSame(
            1,
            $time_slots_day->nextSlot('', 'mon 20:30'),
            'Tasks earliest start should give a next slot key.'
        );
        $this->assertSame(
            -1,
            $time_slots_day->nextSlot('', 'tue 20:30'),
            'Tasks earliest start should give NO next slot key.'
        );
    }

    public function testSplitByTimepoint()
    {
        $time_slots_day = new TimeSlotsDay("10:00-20:00", 'mon');
        $time_point = new TimePoint('mon 16:00');

        // first some initial test
        $this->assertSame(
            1,
            count($time_slots_day->getSlots()),
            'There should initially only be 1 slot for the TimeSlotsDay.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getLengthOfSlot(0),
            'The original slot should be 10 hours / 600 minutes in length.'
        );
        $this->assertSame(
            600,
            $time_slots_day->getLengthOfSlot(0, true),
            'The original slot should be 10 hours / 600 minutes in length for initial as well.'
        );

        // now the splitting and ongoing tests
        $split_success = $time_slots_day->splitSlotByTimepoint($time_point);
        $this->assertTrue(
            $split_success,
            'Splitting TimeSlotsDay by TimePoint was not successful.'
        );
        $this->assertSame(
            2,
            count($time_slots_day->getSlots()),
            'There should now be 2 slots after splitting TimeSlotsDay by TimePoint.'
        );
        $this->assertSame(
            360,
            $time_slots_day->getLengthOfSlot(0),
            'The new first slot should only be 6 hours / 360 minutes in length.'
        );
        $this->assertSame(
            360,
            $time_slots_day->getLengthOfSlot(0, true),
            'The new first slot should only be 6 hours / 360 minutes in length for initial as well.'
        );
        $this->assertSame(
            240,
            $time_slots_day->getLengthOfSlot(1),
            'The new second slot should only be 4 hours / 240 minutes in length.'
        );
        $this->assertSame(
            240,
            $time_slots_day->getLengthOfSlot(1, true),
            'The new second slot should only be 4 hours / 240 minutes in length for initial as well.'
        );
    }
}
