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
     * A CalDAV event from the CalDAVFetcher. It's no
     * full CalDAV event, which might exist in the real
     * world. It's probably just a trimmed down version
     * of it.
     *
     * This method will return another kind of representation
     * of the given CalDAV event with which this class can
     * continue working.
     *
     * @param  array $caldav_event
     * @return array
     */
    public static function convertSingleCalDAVEvent($caldav_event)
    {
        return [
            'title' => $caldav_event['title'],
            'calendar' => $caldav_event['calendar'],
            'start' => new \DateTime($caldav_event['start']),
            'end' => new \DateTime($caldav_event['end']),
        ];
    }
}
