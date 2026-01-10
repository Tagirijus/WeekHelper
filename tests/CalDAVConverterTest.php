<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helper/CalDAVConverter.php';
require_once __DIR__ . '/../Helper/CalDAVFetcher.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\CalDAVConverter;
use Kanboard\Plugin\WeekHelper\Helper\CalDAVFetcher;


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
}
