<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;


/**
 * This class is for overwriting the given tasks spent times.
 */
class TimetaggerTranscriber
{
    /**
     * The TimetaggerFetcher instance used for fetching the
     * Timetagger tasks. And for returning these events
     * accordingly.
     *
     * @var TimetaggerFetcher
     **/
    var $timetagger_fetcher;

    /**
     * The tasks, which might get remaining tracked time,
     * if no open tasks exist. The structure of the
     * array is:
     *     [
     *         timetagger_tags string => [task]
     *     ]
     *
     * @var array
     **/
    var $remaining_done_tasks = [];

    /**
     * The tasks, which might get remaining tracked time.
     * The structure of the
     * array is:
     *     [
     *         timetagger_tags string => [task]
     *     ]
     *
     * @var array
     **/
    var $remaining_open_tasks = [];

    public function __construct($timetagger_fetcher)
    {
        $this->timetagger_fetcher = $timetagger_fetcher;
    }

    /**
     * Get a timetagger_tags string, which will be available
     * in a task, maybe. It's like "b,c,a". This method will
     * sort this given string and output it as a string again
     * "a,b,c".
     *
     * @param  string $timetagger_tags
     * @return string
     */
    public static function getTimetaggerTagsSorted($timetagger_tags)
    {
        $array = explode(',', $timetagger_tags);
        sort($array);
        return implode(',', $array);
    }

    /**
     * Checks if given string "tags" are inside the given array,
     * which probably is a TimetaggerEvents tags array.
     *
     * @param string $csv_tags
     * @param array  $tags
     * @return boolean
     */
    public static function tagsMatch($csv_tags, $tags) {
        $required = array_filter(array_map('trim', explode(',', $csv_tags)), function($v) {
            return $v !== '' && $v !== null;
        });

        if (empty($required)) {
            return false;
        }

        $required = array_map('mb_strtolower', $required);
        $tags = array_map('mb_strtolower', $tags);

        $set = array_flip($tags);

        foreach ($required as $tag) {
            if (! isset($set[$tag])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper: is a task "done" by the rules:
     * - nb_subtasks == 0 OR
     * - nb_subtasks - nb_completed_subtasks == 0
     *
     * Keys may be missing; default to 0. This also
     * means: if a task has no subtasks, it is always
     * considered to be done!
     *
     * @param array $task
     * @return bool
     */
    protected static function isTaskDone($task)
    {
        $nb_subtasks = (int) ($task['nb_subtasks'] ?? 0);
        $nb_completed = (int) ($task['nb_completed_subtasks'] ?? 0);

        if ($nb_subtasks === 0) {
            return true;
        }

        return ($nb_subtasks - $nb_completed) === 0;
    }

    /**
     * The functionality to overwrite a tasks spent time
     * with the event tracked times.
     *
     * @param  array &$task
     * @param  string $timetagger_tags
     */
    protected function overwriteSpentTime(&$task, $timetagger_tags)
    {
        // for each event, find matching tags and distribute
        foreach ($this->timetagger_fetcher->getEvents() as $event) {
            // available hours for this event
            $available = $event->getAvailable();
            if ($available <= 0) {
                continue;
            }

            $event_tags = $event->getTags() ?: [];

            if (self::tagsMatch($timetagger_tags, $event_tags)) {
                // change times from hours into seconds
                // and reset time_spent
                if (!isset($task['_timetagger_transcribing'])) {
                    $task['_timetagger_transcribing'] = true;
                    $task['time_estimated'] = TimeHelper::hoursToSeconds($task['time_estimated']);
                    $task['time_spent'] = 0;
                }
            } else {
                continue;
            }

            $capacity = (
                self::isTaskDone($task) ?
                max(0, $task['time_estimated'] - $task['time_spent'])
                : $available
            );
            if ($capacity <= 0) {
                // nothing to fill for this done task, break the loop early
                break;
            }

            // now give this task the time finally
            $alloc = min($capacity, $available);
            $task['time_spent'] += $alloc;
            $available -= $alloc;
            $event->distribute($alloc);
        }

        // convert task times back to hours
        if (($task['_timetagger_transcribing'] ?? false)) {
            unset($task['_timetagger_transcribing']);
            $task['time_estimated'] = TimeHelper::secondsToHours($task['time_estimated']);
            $task['time_spent'] = TimeHelper::secondsToHours($task['time_spent']);
        }
    }

    /**
     * This method should be called after the other method
     * overwriteSpentTimeForTask() was used inside a loop
     * for all tasks. This should have filled up the internal
     * attributes so that this method knows which tasks
     * now can get the remaining events time.
     */
    public function overwriteSpentTimesForRemainingTasks()
    {
        if (empty($this->timetagger_fetcher->getEvents())) {
            // nothing to do
            return;
        }
        foreach ($this->remaining_open_tasks as $timetagger_tags => &$task) {
            $this->overwriteSpentTime($task, $timetagger_tags);
        }
        foreach ($this->remaining_done_tasks as $timetagger_tags => &$task) {
            $this->overwriteSpentTime($task, $timetagger_tags);
        }
    }

    /**
     * Overwrite the spent times for the given task.
     * It will distribute the events time to a task,
     * if the timetagger tags fit and if the task is
     * considered "done".
     *
     * It the tags fit, but the task is still open, it
     * will be held back so that on a later method
     * call of overwriteSpentTimesForRemainingTasks()
     * the remaining time will be set.
     *
     * The logic is:
     * - distribute the tracked time to done tasks first
     * - if open tasks with the same tags exist,
     *   give them the remaining tracked time
     * - if no other tasks exist, give the remaining
     *   tracked time to the last done task.
     *
     * @param  array &$task
     */
    public function overwriteSpentTimeForTask(&$task)
    {
        $timetagger_tags = self::getTimetaggerTagsSorted($task['timetagger_tags'] ?? '');
        if (empty($timetagger_tags)) {
            return;
        }
        if (self::isTaskDone($task)) {
            $this->remaining_done_tasks[$timetagger_tags] = &$task;
        } else {
            $this->remaining_open_tasks[$timetagger_tags] = &$task;
            // open tasks will get event times in another method.
            // so it's just about adding the task to the internal
            // array to know that this task might get tracked
            // time later.
            return;
        }

        if (empty($this->timetagger_fetcher->getEvents())) {
            // nothing to do
            return;
        }

        $this->overwriteSpentTime($task, $timetagger_tags);
    }
}
