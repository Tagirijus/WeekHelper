<?php

namespace Kanboard\Plugin\WeekHelper\Model;


class TaskInfoParser
{

    /**
     * With this method I can get info additinoal for the given
     * task as variables from the description text. I guess
     * technically it is possible to extend a Kanbaord task
     * with new fields or so. I did not want to dig into this
     * and alos maybe I want to have such additional data
     * optional and not "always available". This parsing
     * is similar to the project info parsing. There will be
     * a "key" and data after this keys equal sign.
     *
     * @param  array $task
     * @return array
     */
    public static function getTaskInfoByTask($task)
    {
        $data = [
            // the task can overwrite the project_type string, if
            // a string is given. by default this one here is NULL.
            // so this means: the task can overwrite the projetc type
            // (for this task only) with an empty string, if something
            // is given. otherwise it is NULL by default here internally.
            // if nothing is given then from the user, the internal
            // project type won't overwrite the project type, which exists
            // on the outside.
            'project_type' => null,

            // this is an info for the automatic planner so that it knows
            // when this task can be planned earliest in the week. this can
            // become handy, if a task should be rather planned on a specific
            // time directly. e.g. the user could add a sorting for all tasks
            // on this key, so that tasks with such key would be processed
            // first. maybe this value becomes handy in other situations as
            // well. it's no direct timeslot planning, but rather some kind of
            // "you may plan this task earliest on this day + time only". so
            // maybe there are tasks, which should only be planned after
            // Tuesday or so.
            'plan_from' => '',

            // A string representing the Timetagger tags, comma separated,
            // which should represent this task, thus the internal Timetagger
            // implementation / connection will be able to fill this tasks
            // spent time.
            'timetagger_tags' => '',
        ];

        self::parseData($data, $task['description']);

        return $data;
    }

    /**
     * Parse the wanted data from the given description string
     * into the internal data class attribute.
     *
     * @param array   &$data
     * @param  string $description
     */
    public static function parseData(&$data, $description)
    {
        $lines = explode("\n", $description ?? '');
        foreach ($lines as &$line) {

            $line = trim($line);

            if (str_starts_with($line, 'project_type=')) {
                $data['project_type'] = str_replace('project_type=', '', $line);

            } elseif (str_starts_with($line, 'plan_from=')) {
                $data['plan_from'] = str_replace('plan_from=', '', $line);

            } elseif (str_starts_with($line, 'timetagger_tags=')) {
                $data['timetagger_tags'] = str_replace('timetagger_tags=', '', $line);

            }
        }

        // project_type is special. by default internally it is NULL. this will
        // be deleted then. otherwise maybe a string is given (even an empty one).
        // this can overwrite the project type later on the outside of this class.
        if (is_null($data['project_type'])) {
            unset($data['project_type']);
        }
    }

    /**
     * Use the internal parser and directly extend the given task array
     * with the newly fetched data from this class.
     *
     * @param  array &$task
     */
    public static function extendTask(&$task)
    {
        $fetched = self::getTaskInfoByTask($task);
        $fetched['info_parsed'] = true;
        $task = array_merge($task, $fetched);
    }
}
