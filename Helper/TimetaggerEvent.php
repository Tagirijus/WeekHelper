<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


class TimetaggerEvent
{
    /**
     * The Timetagger key.
     *
     * @var string
     **/
    var $key = '';

    /**
     * The modified timestamp.
     *
     * @var integer
     **/
    var $modified = 0;

    /**
     * The modified timestamp from the server.
     *
     * @var float
     **/
    var $modified_server = 0.0;

    /**
     * The start timestamp (t1).
     *
     * @var integer
     **/
    var $start = 0;

    /**
     * The end timestamp (t2).
     *
     * @var integer
     **/
    var $end = 0;

    /**
     * The description text.
     *
     * @var string
     **/
    var $description = '';

    /**
     * The parsed tags from the description.
     *
     * @var array
     **/
    var $tags = [];

    /**
     * Set the key string.
     *
     * @param string $key
     */
    public function setKey($key = '')
    {
        $this->key = $key;
    }

    /**
     * Get the key string.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the modified timestamp.
     *
     * @param integer $modified
     */
    public function setModified($modified = 0)
    {
        $this->modified = $modified;
    }

    /**
     * Get the modified timestamp.
     *
     * @return integer
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set the modified_server timestamp.
     *
     * @param float $modified_server
     */
    public function setModifiedServer($modified_server = 0.0)
    {
        $this->modified_server = $modified_server;
    }

    /**
     * Get the modified_server timestamp.
     *
     * @return integer
     */
    public function getModifiedServer()
    {
        return $this->modified_server;
    }

    /**
     * Set the start timestamp.
     *
     * @param integer $start
     */
    public function setStart($start = 0)
    {
        $this->start = $start;
    }

    /**
     * Get the start timestamp.
     *
     * @return integer
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the end timestamp.
     *
     * @param integer $end
     */
    public function setEnd($end = 0)
    {
        $this->end = $end;
    }

    /**
     * Get the end timestamp.
     *
     * @return integer
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set the description string.
     *
     * @param string $description
     */
    public function setDescription($description = '')
    {
        $this->description = $description;
        $this->setTags(
            self::parseTagsFromString($description)
        );
    }

    /**
     * Get the description string.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Parse the tags from the given string, which probably
     * is the description string.
     *
     * @param string  $description
     * @return array
     */
    public static function parseTagsFromString($description = '')
    {
        preg_match_all('/#([^\s#]+)/u', $description, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Set the tags array.
     *
     * @param array $tags
     */
    public function setTags($tags = [])
    {
        $this->tags = $tags;
    }

    /**
     * Get the tags array.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Running events in Timetagger seem to have the
     * same start and end.
     *
     * @return boolean
     */
    public function isRunning()
    {
        return $this->start == $this->end;
    }

    /**
     * Create / instantiate a class with the given JSON string.
     *
     * @param  string $json
     * @return static
     */
    public static function fromJSONString($json = '')
    {
        $data = json_decode($json, true);

        $instance = new static();

        // either a full JSON with the key "records" is given,
        // then use the first item of it, if it exists; otherwise
        // it shall be an empty array as a fallback
        if (array_key_exists('records', $data)) {
            $data = $data['records'][0] ?? [];
        }

        // now suppose that the given / chosen array is a Timetagger
        // array with the needed keys

        $instance->setKey($data['key'] ?? '');
        $instance->setModified((integer) $data['mt'] ?? 0);
        $instance->setModifiedServer((float) $data['st'] ?? 0.0);
        $instance->setStart((integer) $data['t1'] ?? 0);
        $instance->setEnd((integer) $data['t2'] ?? 0);
        $instance->setDescription($data['ds'] ?? '');

        return $instance;
    }
}
