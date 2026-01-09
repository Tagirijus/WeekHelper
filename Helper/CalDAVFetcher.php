<!--

This whole class is a tiny bit magic to me (not 100%, but parts of it).
I have to confess here: I used LLM to generate parts of it, since I
really do not understand the whole CalDAV fetching process. I tested it
and it seem to work. But still: please use at your own risk! I think it
should be save ... but I am not sure, since I really do not quite understand
this whole XML query part. It really looks gibberish and I do not know, why
people came up with such weird query language. Also I do not know why
CalDAV has to be so complicated in 2026 ... Maybe I am just a monkey.

-->


<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

class CalDAVFetcher
{
    /**
     * The user name for the calendars.
     *
     * @var string
     **/
    var $user = '';

    /**
     * The app password for the calendars.
     *
     * @var string
     **/
    var $app_pwd = '';

    public function __construct($user, $app_pwd)
    {
        $this->user = $user;
        $this->app_pwd = $app_pwd;
    }

    /**
     * Fetch CalDAV events for the given calendar (url) from
     * start to end.
     *
     * @param  string $calendar_url
     * @param  string $startUtc
     * @param  string $endUtc
     * @return array
     */
    public function fetchEvents($calendar_url, $startUtc, $endUtc)
    {
        $xml = self::buildCalendarQueryXml($startUtc, $endUtc);
        $response = self::caldavRequest($calendar_url, $this->user, $this->app_pwd, 'REPORT', $xml, ['Depth: 1']);
        if ($response === false) {
            return [];
        }
        // Expect multistatus XML; parse responses and extract calendar-data nodes
        $calendarDatas = self::extractCalendarDataFromMultistatus($response);
        $events = [];
        foreach ($calendarDatas as $ical) {
            foreach (self::parseICalEvents($ical) as $evt) {
                $evt['source'] = $calendar_url;
                $evt['calendar'] = basename($calendar_url);
                $events[] = $evt;
            }
        }
        return $events;
    }

    /**
     * Build this weirdo CalDAV XML Query stuff. I hate it. I did
     * not write this on my own, tbh . . .
     *
     * @param  string $startUtc
     * @param  string $endUtc
     * @return string
     */
    protected static function buildCalendarQueryXml(string $startUtc, string $endUtc) {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' . "\n"
            . '  <d:prop>' . "\n"
            . '    <d:getetag/>' . "\n"
            . '    <c:calendar-data/>' . "\n"
            . '  </d:prop>' . "\n"
            . '  <c:filter>' . "\n"
            . '    <c:comp-filter name="VCALENDAR">' . "\n"
            . '      <c:comp-filter name="VEVENT">' . "\n"
            . '        <c:time-range start="' . htmlspecialchars($startUtc) . '" end="' . htmlspecialchars($endUtc) . '"/>' . "\n"
            . '      </c:comp-filter>' . "\n"
            . '    </c:comp-filter>' . "\n"
            . '  </c:filter>' . "\n"
            . '</c:calendar-query>';
    }

    /**
     * Do a CalDAV request. I did not write this on my own, tbh.
     *
     * @param  string $url
     * @param  string $user
     * @param  string $pass
     * @param  string $method
     * @param  string $body
     * @param  array  $extraHeaders
     * @return boolean
     */
    protected static function caldavRequest(
        string $url,
        string $user,
        string $pass,
        string $method = 'REPORT',
        string $body = '',
        array $extraHeaders = []
    ) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $extraHeaders[] = 'Content-Type: application/xml; charset=utf-8';
            $extraHeaders[] = 'Content-Length: ' . strlen($body);
        }
        $extraHeaders[] = 'User-Agent: Kanboard-CalDAV-WeekHelper/1.0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
        // some servers need Expect disabled
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($extraHeaders, ['Expect:']));
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($res === false) {
            error_log('CalDAV request failed: ' . $err);
            return false;
        }
        if ($code >= 400) {
            error_log('CalDAV HTTP error: ' . $code . ' for ' . $url);
            return false;
        }
        return $res;
    }

    /**
     * Magic stuff is happening. Again I did not write this on my own, tbh.
     * Did I say that I really hate this weird CalDAV stuff?
     *
     * @param  string $xmlString
     * @return array
     */
    protected static function extractCalendarDataFromMultistatus(string $xmlString) {
        $result = [];
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xmlString);
        if ($doc === false) {
            return $result;
        }
        // $namespaces = $doc->getNamespaces(true);
        // responses under multistatus/response
        $responses = $doc->xpath('//d:response') ?: [];
        foreach ($responses as $resp) {
            // calendar-data may be namespaced under caldav
            $cals = $resp->xpath('.//c:calendar-data') ?: $resp->xpath('.//*[local-name()="calendar-data"]') ?: [];
            foreach ($cals as $c) {
                $ical = trim((string)$c);
                if ($ical !== '') {
                    $result[] = $ical;
                }
            }
        }
        return $result;
    }

    /**
     * Seems to be some parsing of the raw ICAL events string ...
     *
     * @param  string $ical
     * @return array
     */
    protected static function parseICalEvents(string $ical) {
        $lines = preg_split("/\r\n|\n|\r/", $ical);
        $events = [];
        $inEvent = false;
        $buffer = [];
        foreach ($lines as $line) {
            // handle folded lines (lines starting with space or tab are continuations)
            if (preg_match('/^[ \t]/', $line) && !empty($buffer)) {
                $buffer[count($buffer)-1] .= ltrim($line);
                continue;
            }
            if (trim($line) === 'BEGIN:VEVENT') {
                $inEvent = true;
                $buffer = [];
                continue;
            }
            if (trim($line) === 'END:VEVENT') {
                $inEvent = false;
                $events[] = self::parseEventBlock($buffer);
                $buffer = [];
                continue;
            }
            if ($inEvent) {
                $buffer[] = $line;
            }
        }
        return array_values(array_filter($events));
    }

    /**
     * Some further parsing of internal ICAL event block or so.
     *
     * @param  array  $lines
     * @return array
     */
    protected static function parseEventBlock(array $lines) {
        $data = [];
        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '') continue;
            // split at first colon (but ignore colons in parameters)
            $parts = explode(':', $line, 2);
            if (count($parts) < 2) continue;
            $left = $parts[0];
            $value = $parts[1];
            $leftUpper = strtoupper($left);
            if (strpos($leftUpper, 'DTSTART') === 0) {
                $data['dtstart_raw'] = $value;
                $data['dtstart_params'] = self::parseParams($left);
                $data['start'] = self::normalizeICalDate($value, $data['dtstart_params'] ?? []);
            } elseif (strpos($leftUpper, 'DTEND') === 0) {
                $data['dtend_raw'] = $value;
                $data['dtend_params'] = self::parseParams($left);
                $data['end'] = self::normalizeICalDate($value, $data['dtend_params'] ?? []);
            } elseif (strpos($leftUpper, 'SUMMARY') === 0) {
                $data['title'] = self::decodeICalValue($value);
            } elseif (strpos($leftUpper, 'UID') === 0) {
                $data['uid'] = $value;
            } elseif (strpos($leftUpper, 'DTSTAMP') === 0 && !isset($data['dtstamp'])) {
                $data['dtstamp'] = self::normalizeICalDate($value, self::parseParams($left));
            }
        }
        // require start and title at minimum
        if (!isset($data['start'])) return null;
        if (!isset($data['end'])) {
            // treat as zero-length event if no DTEND; could also parse DURATION
            $data['end'] = $data['start'];
        }
        if (!isset($data['title'])) $data['title'] = '';
        if (!isset($data['uid'])) $data['uid'] = uniqid('evt_', true);
        return [
            'start' => $data['start'],
            'end' => $data['end'],
            'title' => $data['title'],
            'uid' => $data['uid'],
        ];
    }

    /**
     * Parsing of params of the ICAL event block or so.
     *
     * @param  string $left
     * @return array
     */
    protected static function parseParams(string $left) {
        // left might be like DTSTART;TZID=Europe/Berlin
        $parts = explode(';', $left);
        array_shift($parts); // first is key
        $params = [];
        foreach ($parts as $p) {
            if (strpos($p, '=') !== false) {
                [$k,$v] = explode('=', $p, 2);
                $params[strtoupper($k)] = $v;
            }
        }
        return $params;
    }

    /**
     * Normalizing of some date string or so.
     *
     * @param  string $val
     * @param  array  $params
     * @return string
     */
    protected static function normalizeICalDate(string $val, array $params = []) {
        // Returns ISO8601 UTC string (YYYY-MM-DDTHH:MM:SSZ) when possible.
        $val = trim($val);
        // DATE (YYYYMMDD) -> treat as all-day: make start at 00:00 and end exclusive could be handled by caller
        if (preg_match('/^\d{8}$/', $val)) {
            $dt = \DateTime::createFromFormat('Ymd', $val, new \DateTimeZone('UTC'));
            if ($dt) return $dt->format('Y-m-d\\T00:00:00\\Z');
        }
        // If value ends with Z -> UTC
        if (substr($val, -1) === 'Z') {
            $dt = \DateTime::createFromFormat('Ymd\THis\Z', $val, new \DateTimeZone('UTC'));
            if ($dt) return $dt->format('Y-m-d\\TH:i:s\\Z');
            // fallback generic parse
            $dt = new \DateTime($val, new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d\\TH:i:s\\Z');
        }
        // If TZID param present, try to use it
        if (isset($params['TZID'])) {
            try {
                $tz = new \DateTimeZone($params['TZID']);
            } catch (\Exception $e) {
                $tz = new \DateTimeZone('UTC');
            }
            // try formats
            $dt = \DateTime::createFromFormat('Ymd\THis', $val, $tz) ?: \DateTime::createFromFormat('Ymd\THis', $val);
            if ($dt) {
                $dt->setTimezone(new \DateTimeZone($params['TZID']));
                return $dt->format('Y-m-d\\TH:i:s\\Z');
            }
        }
        // No TZ info: assume local naive time; parse and convert to UTC (assume server default TZ)
        $dt = \DateTime::createFromFormat('Ymd\THis', $val) ?: @new \DateTime($val);
        if ($dt) {
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d\\TH:i:s\\Z');
        }
        // fallback: return raw
        return $val;
    }

    /**
     * Decode some common escapes in iCal text values.
     *
     * @param  string $v
     * @return string
     */
    protected static function decodeICalValue(string $v) {
        // decode common escapes in iCal text values
        $v = str_replace(['\\n', '\\N'], "\n", $v);
        $v = str_replace('\\,', ',', $v);
        $v = str_replace('\\;', ';', $v);
        $v = str_replace('\\\\', '\\', $v);
        return $v;
    }
}