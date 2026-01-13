<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Plugin\WeekHelper\Helper\TimetaggerEvent;


class TimetaggerFetcher
{
    /**
     * The url of the Timetagger installation.
     *
     * @var string
     **/
    var $url = '';

    /**
     * The auth token for security.
     *
     * @var string
     **/
    var $authtoken = '';

    /**
     * Possible optional cookies to set during
     * the API call, if such thing might be needed
     * for further security reasons or so.
     *
     * @var array
     **/
    var $cookies = [];

    /**
     * The internal TimetaggerEvent instances.
     *
     * @var TImetaggerEvent[]
     **/
    var $events = [];

    /**
     * Initialize the TimetaggerFetcher instance with the basical
     * needed infos.
     *
     * @param string $url
     * @param string $authtoken
     * @param array  $cookies
     */
    public function __construct($url = '', $authtoken = '', $cookies = [])
    {
        $this->url = $url;
        $this->authtoken = $authtoken;
        $this->cookies = $cookies;
    }

    /**
     * Return the internal TimetaggerEvent array, or even
     * a TimetaggerEvent from the given key directly.
     *
     * If it does not exist, it will return null.
     *
     * @param  null|integer $key
     * @return array|TimetaggerEvent|null
     */
    public function getEvents($key = null)
    {
        if (is_null($key)) {
            return $this->events;
        } else {
            return $this->events[$key] ?? null;
        }
    }

    /**
     * Create array of TimetaggerEvents from the given JSON string.
     *
     * @param  string $json
     * @return array
     */
    public static function eventsFromJSONString($json = '')
    {
        $data = json_decode($json, true);
        $events = [];

        if (array_key_exists('records', $data)) {
            foreach ($data['records'] as $event) {
                $te = TimetaggerEvent::fromArray($event);
                $events[] = $te;
            }
        }

        return $events;
    }
}
