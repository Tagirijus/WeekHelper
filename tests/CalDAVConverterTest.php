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
            'thu 0:00-23:59 active thu (calendar_name)',
            $converted,
            'CalDAVConverter event was not translated correctly into a string.'
        );
    }
}
