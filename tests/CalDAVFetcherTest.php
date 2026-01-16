<?php

declare(strict_types=1);

namespace Kanboard\Plugin\WeekHelper\tests;

require_once __DIR__ . '/../Model/CalDAVFetcher.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\CalDAVFetcher;


final class CalDAVFetcherTest extends TestCase
{
    public static string $caldav_response;
    public static array $caldav_entries;
    public static array $caldav_events;

    public static function setUpBeforeClass(): void
    {
        self::$caldav_response = '<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns"><d:response><d:href>/remote.php/dav/calendars/manu/personal/EAD6F7E9-2066-4515-A888-B0636209943E.ics</d:href><d:propstat><d:prop><d:getetag>&quot;532cb8dc38fc790f1d97c8cfe3dc2d61&quot;</d:getetag><cal:calendar-data>BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 5.0.9//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:20260109T221405Z
DTSTAMP:20260109T221422Z
LAST-MODIFIED:20260109T221422Z
SEQUENCE:2
UID:e93d7f35-d34a-43ad-a8de-6c0b24da4840
DTSTART;TZID=Europe/Berlin:20260109T100000
DTEND;TZID=Europe/Berlin:20260109T110000
STATUS:CONFIRMED
SUMMARY:active fri
END:VEVENT
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
END:VCALENDAR</cal:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response><d:response><d:href>/remote.php/dav/calendars/manu/personal/DF79DE48-BA4C-48BF-982E-8D0C67D950C6.ics</d:href><d:propstat><d:prop><d:getetag>&quot;eba30309868bc551f0f6fcb352e0b6a1&quot;</d:getetag><cal:calendar-data>BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 5.0.9//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:20260110T061612Z
DTSTAMP:20260110T061623Z
LAST-MODIFIED:20260110T061623Z
SEQUENCE:2
UID:5d9a838e-eabf-4d90-8b2b-4401e5ba0008
DTSTART;VALUE=DATE:20260108
DTEND;VALUE=DATE:20260109
STATUS:CONFIRMED
SUMMARY:active thu
END:VEVENT
END:VCALENDAR</cal:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response><d:response><d:href>/remote.php/dav/calendars/manu/personal/DF79DE48-BA4C-48BF-982E-8D0C67D950C6.ics</d:href><d:propstat><d:prop><d:getetag>&quot;eba30309868bc551f0f6fcb352e0b6a1&quot;</d:getetag><cal:calendar-data>BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 5.0.9//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:20260110T081612Z
DTSTAMP:20260110T081623Z
LAST-MODIFIED:20260110T081623Z
SEQUENCE:2
UID:5d9a838e-eabf-4d90-8b2b-4401e5ba0009
DTSTART;VALUE=DATE:20260115
DTEND;VALUE=DATE:20260116
STATUS:CONFIRMED
SUMMARY:planned thu
END:VEVENT
END:VCALENDAR</cal:calendar-data></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response></d:multistatus>';

    self::$caldav_entries = [
        'BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 5.0.9//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:20260109T221405Z
DTSTAMP:20260109T221422Z
LAST-MODIFIED:20260109T221422Z
SEQUENCE:2
UID:e93d7f35-d34a-43ad-a8de-6c0b24da4840
DTSTART;TZID=Europe/Berlin:20260109T100000
DTEND;TZID=Europe/Berlin:20260109T110000
STATUS:CONFIRMED
SUMMARY:active fri
END:VEVENT
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
END:VCALENDAR',
    'BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 5.0.9//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:20260110T061612Z
DTSTAMP:20260110T061623Z
LAST-MODIFIED:20260110T061623Z
SEQUENCE:2
UID:5d9a838e-eabf-4d90-8b2b-4401e5ba0008
DTSTART;VALUE=DATE:20260108
DTEND;VALUE=DATE:20260109
STATUS:CONFIRMED
SUMMARY:active thu
END:VEVENT
END:VCALENDAR',
    'BEGIN:VCALENDAR
PRODID:-//IDN nextcloud.com//Calendar app 5.0.9//EN
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
CREATED:20260110T081612Z
DTSTAMP:20260110T081623Z
LAST-MODIFIED:20260110T081623Z
SEQUENCE:2
UID:5d9a838e-eabf-4d90-8b2b-4401e5ba0009
DTSTART;VALUE=DATE:20260115
DTEND;VALUE=DATE:20260116
STATUS:CONFIRMED
SUMMARY:planned thu
END:VEVENT
END:VCALENDAR'
    ];

    self::$caldav_events = [
            [
                'start' => new \DateTime(
                    '2026-01-09T10:00:00',
                    new \DateTimeZone(date_default_timezone_get())
                ),
                'end' => new \DateTime(
                    '2026-01-09T11:00:00',
                    new \DateTimeZone(date_default_timezone_get())
                ),
                'title' => 'active fri',
                'uid' => 'e93d7f35-d34a-43ad-a8de-6c0b24da4840',
                'source' => 'calendar_url_here/calendar_name',
                'calendar' => 'calendar_name',
            ],
            [
                'start' => new \DateTime(
                    '2026-01-08T00:00:00',
                    new \DateTimeZone(date_default_timezone_get())
                ),
                'end' => new \DateTime(
                    '2026-01-09T00:00:00',
                    new \DateTimeZone(date_default_timezone_get())
                ),
                'title' => 'active thu',
                'uid' => '5d9a838e-eabf-4d90-8b2b-4401e5ba0008',
                'source' => 'calendar_url_here/calendar_name',
                'calendar' => 'calendar_name',
            ],
            [
                'start' => new \DateTime(
                    '2026-01-15T00:00:00',
                    new \DateTimeZone(date_default_timezone_get())
                ),
                'end' => new \DateTime(
                    '2026-01-16T00:00:00',
                    new \DateTimeZone(date_default_timezone_get())
                ),
                'title' => 'planned thu',
                'uid' => '5d9a838e-eabf-4d90-8b2b-4401e5ba0009',
                'source' => 'calendar_url_here/calendar_name',
                'calendar' => 'calendar_name',
            ]
        ];
    }

    public function testExtractCalendarDataFromMultistatus()
    {
        $this->assertSame(
            self::$caldav_entries,
            CalDAVFetcher::extractCalendarDataFromMultistatus(self::$caldav_response),
            'CalDAVFetcher converter from raw CalDAV response to separated entries doe snot work.'
        );
    }

    public function testRawDatasToEvents()
    {
        $output = CalDAVFetcher::rawDatasToEvents(
            CalDAVFetcher::extractCalendarDataFromMultistatus(self::$caldav_response),
            'calendar_url_here/calendar_name'
        );

        // same DateTimes will still be different objects internally. So I have to
        // convert both first ... unluckily. I will replace the DateTime instances
        // with equally formatted strings instead.
        foreach ($output as &$event) {
            $event['start'] = $event['start']->format('Y-m-d H:i:s');
            $event['end'] = $event['end']->format('Y-m-d H:i:s');
        }
        $caldav_events_original = self::$caldav_events;
        foreach ($caldav_events_original as &$event) {
            $event['start'] = $event['start']->format('Y-m-d H:i:s');
            $event['end'] = $event['end']->format('Y-m-d H:i:s');
        }

        $this->assertSame(
            $caldav_events_original,
            $output,
            'rawDatasToEvents() does not convert as intended.'
        );
    }
}
