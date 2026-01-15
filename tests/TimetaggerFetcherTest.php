<?php

declare(strict_types=1);

namespace Kanboard\Plugin\WeekHelper\tests;

require_once __DIR__ . '/../Helper/TimetaggerFetcher.php';
require_once __DIR__ . '/../Helper/TimetaggerEvent.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimetaggerFetcher;


final class TimetaggerFetcherTest extends TestCase
{
    public function testFromJSONString()
    {
        $json = '{"records": [{"key": "ZXUpDgHD", "mt": 1768320606, "t1": 1768320605, "t2": 1768320605, "ds": "#tagi #kanboard-todo  #code", "st": 1768320607.813087}]}';
        $events = TimetaggerFetcher::eventsFromJSONString($json);

        $te = $events[0];

        $this->assertSame(
            'ZXUpDgHD',
            $te->getKey(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320606,
            $te->getModified(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320607.813087,
            $te->getModifiedServer(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320605,
            $te->getStart(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320605,
            $te->getEnd(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
        $this->assertTrue(
            $te->isRunning(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
        $this->assertSame(
            [
                'tagi',
                'kanboard-todo',
                'code'
            ],
            $te->getTags(),
            'Parsing of TimetaggerFetcher from JSON did not work as intended.'
        );
    }

    public function testQueryBuilder()
    {
        $this->assertSame(
            'timerange=5000-6000&tag=abc%2Cdef%2Cghi&running=1',
            TimetaggerFetcher::queryBuilder(5000, 6000, 'abc,def,ghi', true),
            'TimetaggerFetcher::queryBuilder() did not work as intended.'
        );
        $this->assertSame(
            'timerange=5000-6000&tag=abc%2Cdef%2Cghi&running=0',
            TimetaggerFetcher::queryBuilder(5000, 6000, 'abc,def,ghi', false),
            'TimetaggerFetcher::queryBuilder() did not work as intended.'
        );
        $this->assertSame(
            'timerange=5000-'.time(),
            TimetaggerFetcher::queryBuilder(5000),
            'TimetaggerFetcher::queryBuilder() did not work as intended.'
        );
    }
}
