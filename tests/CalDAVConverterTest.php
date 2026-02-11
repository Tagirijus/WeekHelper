<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/CalDAVConverter.php';
require_once __DIR__ . '/../tests/CalDAVFetcherTest.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\CalDAVConverter;
use Kanboard\Plugin\WeekHelper\Model\CalDAVFetcher;
use Kanboard\Plugin\WeekHelper\tests\CalDAVFetcherTest;


final class CalDAVConverterTest extends TestCase
{
    public function testParseCalendarUrls()
    {
        $this->assertSame(
            [
                'urls/calendar1',
                'urls/calendar2',
                'urls/calendar3'
            ],
            CalDAVConverter::parseUrls("urls/calendar1\nurls/calendar2\nurls/calendar3"),
            'CalDAVConverter did not fetch calendar URLS correctly.'
        );
    }

    public function testEventToTimeSpanString()
    {
        CalDAVFetcherTest::setUpBeforeClass();
        $caldav_event = CalDAVFetcherTest::$caldav_events[0];
        $converted = CalDAVConverter::eventToTimeSpanString($caldav_event);

        $this->assertSame(
            'fri 10:00-11:00 active fri (calendar_name)',
            $converted,
            'CalDAVConverter event was not translated correctly into a string.'
        );

        // now the full day, which is basically 0:00 on the start day
        // and 0:00 on the following day. it should be converted internally
        // to 0:00 of the start day and 23:59 of the start day
        $caldav_event = CalDAVFetcherTest::$caldav_events[1];
        $converted = CalDAVConverter::eventToTimeSpanString($caldav_event);

        $this->assertSame(
            'thu 23:58-23:59 active thu (calendar_name)',
            $converted,
            'CalDAVConverter event was not translated correctly into a string.'
        );
    }

    public function testGetUtcRangeFor2Weeks()
    {
        [$start, $end] = CalDAVConverter::getRangeFor2Weeks('2026-01-11');
        $this->assertSame(
            '2026-01-05 00:00:00',
            $start->format('Y-m-d H:i:s'),
            'CalDAVConverter::getUtcRangeFor2Weeks did not create the correct starting datetime.'
        );
        $this->assertSame(
            '2026-01-18 23:59:59',
            $end->format('Y-m-d H:i:s'),
            'CalDAVConverter::getUtcRangeFor2Weeks did not create the correct ending datetime.'
        );
    }

    public function testGetEndOfWeekForDatetime()
    {
        $end = CalDAVConverter::getEndOfWeekForDatetime('2026-01-11');
        $this->assertSame(
            '2026-01-11 23:59:59',
            $end->format('Y-m-d H:i:s'),
            'CalDAVConverter::getEndOfWeekForDatetime did not create the correct ending datetime.'
        );
    }

    public function testDistribution()
    {
        // basically create an "empty" CalDAV fetcher and converter;
        // for testing purposes only. If nor $urls stinrg is given,
        // now server CalDAV fetching will be done.
        $caldav_converter = new CalDAVConverter(new CalDAVFetcher('', ''));
        $caldav_converter->distributeEvents(
            CalDAVFetcherTest::$caldav_events,
            '2026-01-10'
        );

        $this->assertSame(
            'active thu',
            $caldav_converter->getEventsActive()[0]['title'],
            'CalDAVConverter->distributeEvents() did not distribute the events correctly.'
        );
        $this->assertSame(
            'active fri',
            $caldav_converter->getEventsActive()[1]['title'],
            'CalDAVConverter->distributeEvents() did not distribute the events correctly.'
        );
        $this->assertSame(
            'planned thu',
            $caldav_converter->getEventsPlanned()[0]['title'],
            'CalDAVConverter->distributeEvents() did not distribute the events correctly.'
        );
    }

    public function testConfigStringGeneration()
    {
        // basically create an "empty" CalDAV fetcher and converter;
        // for testing purposes only. If nor $urls stinrg is given,
        // now server CalDAV fetching will be done.
        $caldav_converter = new CalDAVConverter(new CalDAVFetcher('', ''));
        $caldav_converter->distributeEvents(
            CalDAVFetcherTest::$caldav_events,
            '2026-01-10'
        );

        $expected_active = 'thu 23:58-23:59 active thu (calendar_name)' . "\n";
        $expected_active .= 'fri 10:00-11:00 active fri (calendar_name)' . "\n";
        $this->assertSame(
            $expected_active,
            $caldav_converter->generateBlockingStringForActiveWeek(),
            'CalDAVConverter did not convert blocking config string for active week correctly.'
        );

        $expected_planned = 'thu 23:58-23:59 planned thu (calendar_name)' . "\n";
        $this->assertSame(
            $expected_planned,
            $caldav_converter->generateBlockingStringForPlannedWeek(),
            'CalDAVConverter did not convert blocking config string for active week correctly.'
        );
    }
}
