<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


class CalDAVConverter
{
    /**
     * The CalDAVFetcher instance.
     *
     * @var CalDAVFetcher
     **/
    var $caldav_fetcher;

    /**
     * The array holding all the CalDAV url strings.
     *
     * @var array
     **/
    var $calendar_urls;

    /**
     * Internal arrays of events for the active week.
     *
     * @var array
     **/
    var $events_active = [];

    /**
     * Internal arrays of events for the planned week.
     *
     * @var array
     **/
    var $events_planned = [];

    /**
     * Initialize the instance with the given CalDAVFetcher
     * and the raw calendar urls string from the Kanboard config.
     *
     * @param CalDAVFetcher $caldav_fetcher
     * @param string $urls
     */
    public function __construct($caldav_fetcher, $urls = '')
    {
        $this->caldav_fetcher = $caldav_fetcher;
        $this->calendar_urls = self::parseUrls($urls);
        $caldav_events = $this->getCalDAVEventsFor2Weeks();
        $this->distributeEvents($caldav_events);
    }

    /**
     * Parse the given urls into the calendar_urls.
     *
     * @param  string $urls
     * @return array
     */
    public static function parseUrls($urls)
    {
        $urls = str_replace(["\r\n","\r"], "\n", $urls);
        return array_filter(
            array_map('trim', explode("\n", $urls)),
            fn($s) => $s !== ''
        );
    }

    /**
     * Get all CalDAV events since start of week and only
     * until end of next week (this would be active and
     * planned week).
     *
     * @return array
     */
    public function getCalDAVEventsFor2Weeks()
    {
        $caldav_events = [];

        [$start, $end] = self::getRangeFor2Weeks();

        foreach ($this->calendar_urls as $calendar_url) {
            $caldav_events = array_merge(
                $caldav_events,
                $this->caldav_fetcher->fetchEvents(
                    $calendar_url,
                    $start->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z'),
                    $end->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z')
                )
            );
        }
        return$caldav_events;
    }

    /**
     * Get a range of two weeks for the CalDAV query. The range will be
     * "start of this week" and "end of next week".
     *
     * @param  string $now_dt_string
     * @return array
     */
    public static function getRangeFor2Weeks($now_dt_string = 'now') {
        $tz = new \DateTimeZone(date_default_timezone_get());
        $now = new \DateTime($now_dt_string, $tz);

        // week will start on Monday
        $dayOfWeek = (int) $now->format('N');
        $daysToMonday = $dayOfWeek - 1;

        // Start of week to 00:00:00
        $startLocal = (clone $now)->modify("-{$daysToMonday} days")->setTime(0,0,0);

        // End of next week: two weeks from start minus one second
        $endLocal = (clone $startLocal)->modify('+14 days')->modify('-1 second');

        return [$startLocal, $endLocal];
    }

    /**
     * Get the end of week for the given DateTime string as a new
     * DateTime instance.
     *
     * @param  string $datetime
     * @return DateTime
     */
    public static function getEndOfWeekForDatetime($now_dt_string = 'now')
    {
        // two weeks range first; only to get the start
        [$start, $end] = self::getRangeFor2Weeks($now_dt_string);

        // End of THIS week now: ONE week from start minus one second
        return (clone $start)->modify('+7 days')->modify('-1 second');
    }

    /**
     * Distribute the given CalDAV events into active and planned
     * internal arrays. Also sort them.
     *
     * I have the parameter "now" separately so that I can better
     * write tests for the internal logic, without the need of having
     * to actually fetch real CalDAV events.
     *
     * @param  array  $caldav_events
     * @param  string $now_dt_string
     */
    public function distributeEvents($caldav_events, $now_dt_string = 'now')
    {
        $this->events_active = [];
        $this->events_planned = [];
        $end_of_active_week = self::getEndOfWeekForDatetime($now_dt_string);
        usort($caldav_events, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        foreach ($caldav_events as $event) {
            if ($event['start'] < $end_of_active_week) {
                $this->events_active[] = $event;
            } else {
                $this->events_planned[] = $event;
            }
        }
    }

    /**
     * Return the events for the active week.
     *
     * @return array
     */
    public function getEventsActive()
    {
        return $this->events_active;
    }

    /**
     * Return the events for the planned week.
     *
     * @return array
     */
    public function getEventsPlanned()
    {
        return $this->events_planned;
    }

    /**
     * Convert the given internal event and return a valid
     * timespan string for it, which can be used in the
     * blocking config of the WeekHelper automatic planner
     * configuration.
     *
     * @param  array $event
     * @return string
     */
    public static function eventToTimeSpanString($event)
    {
        $day = strtolower($event['start']->format('D'));
        $start = $event['start']->format('G:i');
        $diff = $event['start']->diff($event['end']);
        $diff = (int) $diff->format('%r%a');
        if ($diff == 1 && $event['end']->format('G:i') == '0:00') {
            $end = '23:59';
        } else {
            $end = $event['end']->format('G:i');
        }
        $title = $event['title'] . ' (' . $event['calendar'] . ')';
        return $day . ' ' . $start . '-' . $end . ' ' . $title;
    }

    /**
     * Generate a string for the config for the blocking
     * tasks for the given week.
     *
     * @param  string $week
     * @return string
     */
    public function generateBlockingStringForWeek($week = 'active')
    {
        $events = $week == 'active' ? $this->getEventsActive() : $this->getEventsPlanned();
        $out = '';
        foreach ($events as $event) {
            $out .= self::eventToTimeSpanString($event);
            $out .= "\n";
        }
        return $out;
    }

    /**
     * Generate a string for the config for the blocking
     * tasks for the active week.
     *
     * @return string
     */
    public function generateBlockingStringForActiveWeek()
    {
        return $this->generateBlockingStringForWeek('active');
    }

    /**
     * Generate a string for the config for the blocking
     * tasks for the planned week.
     *
     * @return string
     */
    public function generateBlockingStringForPlannedWeek()
    {
        return $this->generateBlockingStringForWeek('planned');
    }
}
