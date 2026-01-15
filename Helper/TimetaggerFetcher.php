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
     * @var string
     **/
    var $cookies = '';

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
     * @param string  $cookies
     */
    public function __construct($url = '', $authtoken = '', $cookies = '')
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

    /**
     * Build the query string for the Timetagger API and
     * with the given data.
     *
     * @param  integer $start  Starting timestamp.
     * @param  null|integer $end    Ending timestamp. If null it's "now".
     * @param  null|string $tags     Tags query string; comma separated tag strings.
     * @param  null|boolean $running If given it cna be true or false
     *         to either show only running or only stopped tasks.
     */
    public static function queryBuilder(
        $start,
        $end = null,
        $tags = null,
        $running = null
    )
    {
        $query = [];
        if (is_null($end)) {
            $end = time();
        }
        $query['timerange'] = $start . '-' . $end;
        if (!is_null($tags)) {
            $query['tag'] = $tags;
        }
        if (!is_null($running)) {
            if ($running) {
                $query['running'] = '1';
            } else {
                $query['running'] = '0';
            }
        }
        return http_build_query($query);
    }

    /**
     * Fetch Timetagger events from the Timetagger API, get
     * the JSON string and convert it internall to events.
     *
     * Returns true on success or a string on fail.
     *
     * @param  integer $start  Starting timestamp.
     * @param  null|integer $end    Ending timestamp. If null it's "now".
     * @param  array $tags     Array of tags.
     * @param  null|boolean $running If given it cna be true or false
     *         to either show only running or only stopped tasks.
     * @return boolean|string
     */
    public function fetchEvents(
        $start,
        $end = null,
        $tags = null,
        $running = null
    )
    {
        $query_str = self::queryBuilder($start, $end, $tags, $running);
        $url = $this->url . '?' . $query_str;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            "authtoken: {$this->authtoken}",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!empty($this->cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            return curl_error($ch);
        }

        $this->events = $this->eventsFromJSONString($response);

        return true;
    }
}
