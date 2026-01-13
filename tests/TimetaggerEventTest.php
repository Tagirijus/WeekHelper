<?php

declare(strict_types=1);

namespace Kanboard\Plugin\WeekHelper\tests;

require_once __DIR__ . '/../Helper/TimetaggerEvent.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Helper\TimetaggerEvent;


final class TimetaggerEventTest extends TestCase
{
    public function testParseTagsFromString()
    {
        $description = 'text around should #tagi not #kanboard-todo matter #code at all';
        $expect = [
            'tagi',
            'kanboard-todo',
            'code'
        ];
        $this->assertSame(
            $expect,
            TimetaggerEvent::parseTagsFromString($description),
            'Parsing tags from Timetagger description did not work as intended.'
        );
    }

    public function testParseFromJSON()
    {
        $json = '{"records": [{"key": "ZXUpDgHD", "mt": 1768320606, "t1": 1768320605, "t2": 1768320605, "ds": "#tagi #kanboard-todo  #code", "st": 1768320607.813087}]}';
        $te = TimetaggerEvent::fromJSONString($json);

        $this->assertSame(
            'ZXUpDgHD',
            $te->getKey(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320606,
            $te->getModified(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320607.813087,
            $te->getModifiedServer(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320605,
            $te->getStart(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
        $this->assertSame(
            1768320605,
            $te->getEnd(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
        $this->assertTrue(
            $te->isRunning(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
        $this->assertSame(
            [
                'tagi',
                'kanboard-todo',
                'code'
            ],
            $te->getTags(),
            'Parsing of TimetaggerEvent from JSON did not work as intended.'
        );
    }
}
