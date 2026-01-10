<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/CalDAVConverter.php';
require_once __DIR__ . '/../Helper/CalDAVFetcher.php';
require_once __DIR__ . '/../tests/CalDAVFetcherTest.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\CalDAVConverter;
use Kanboard\Plugin\WeekHelper\Helper\CalDAVFetcher;
use Kanboard\Plugin\WeekHelper\tests\CalDAVFetcherTest;


final class CalDAVConverterTest extends TestCase
{
    public function testInitCalDAVConverter()
    {
        $caldav_fetcher = new CalDAVFetcher('', '');
        $caldav_converter = new CalDAVConverter(
            $caldav_fetcher,
            "urls/calendar1\nurls/calendar2\nurls/calendar3"
        );

        $this->assertSame(
            [
                'urls/calendar1',
                'urls/calendar2',
                'urls/calendar3'
            ],
            $caldav_converter->calendar_urls,
            'CalDAVConverter did not fetch calendar URLS correctly.'
        );
    }

    public function testconvertSingleCalDAVEvent()
    {
        CalDAVFetcherTest::setUpBeforeClass();
        $caldav_event = CalDAVFetcherTest::$caldav_events[0];
        $converted = CalDAVConverter::convertSingleCalDAVEvent($caldav_event);
        $expected = [
            'title' => 'active fri',
            'calendar' => 'calendar_name',
            'start' => new \DateTime('2026-01-09T10:00:00Z'),
            'end' => new \DateTime('2026-01-09T11:00:00Z'),
        ];

        $this->assertSame(
            $expected['title'],
            $converted['title'],
            'convertSingleCalDAVEvent() did not convert the event correctly.'
        );
        $this->assertSame(
            $expected['calendar'],
            $converted['calendar'],
            'convertSingleCalDAVEvent() did not convert the event correctly.'
        );
        $this->assertSame(
            $expected['start']->format('Y-m-d H:i:s'),
            $converted['start']->format('Y-m-d H:i:s'),
            'convertSingleCalDAVEvent() did not convert the event correctly.'
        );
        $this->assertSame(
            $expected['end']->format('Y-m-d H:i:s'),
            $converted['end']->format('Y-m-d H:i:s'),
            'convertSingleCalDAVEvent() did not convert the event correctly.'
        );
    }
}
