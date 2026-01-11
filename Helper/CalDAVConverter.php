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
    public function __construct($caldav_fetcher, $urls)
    {
        $this->caldav_fetcher = $caldav_fetcher;
        $urls = str_replace(["\r\n","\r"], "\n", $urls);
        $this->calendar_urls = array_filter(
            array_map('trim', explode("\n", $urls)),
            fn($s) => $s !== ''
        );
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
}
