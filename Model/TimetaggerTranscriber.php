<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
use Kanboard\Plugin\WeekHelper\Model\TimeCalculator;

/**
 * This class is for overwriting the given tasks spent times.
 */
class TimetaggerTranscriber
{
    /**
     * This attribute is for the logic that for a
     * running Timetagger tracking event the first
     * task, for which this fits, can get the
     * additional key "is_running". But after that
     * no other task shall get this value.
     *
     * @var boolean
     **/
    var $gave_running = false;

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

    /**
     * This array will hold the info, if the spent time
     * for a task was initialized already. This is needed,
     * since otherwise it would happen that tasks with
     * "timetagger_tag" exiting would always got their
     * spent time reset, before adding new time to it.
     *
     * Also this array at the same time will hold the
     * info about which task times should be convert
     * back from seconds to hours. This is important
     * for the final remaining method, which earlier
     * did only loop through the remaining arrays
     * from above. This could lead to tasks not being
     * in these arrays would keep their converted times
     * in seconds.
     *
     * This array will store the data like this:
     *
     *  [
     *      task_id => &$task
     *  ]
     *
     * @var array
     **/
    var $task_times_converted = [];

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
     * Convert the task times back to hours and basically
     * end the overwriting process with this method.
     *
     * @param  array &$task
     */
    protected function taskTimesToHours(&$task)
    {
        $task_id = $task['id'] ?? -1;
        if (array_key_exists($task_id, $this->task_times_converted)) {
            $task['time_estimated'] = TimeHelper::secondsToHours($task['time_estimated']);
            $task['time_spent'] = TimeHelper::secondsToHours($task['time_spent']);
            $task['time_remaining'] = TimeHelper::secondsToHours($task['time_remaining']);
            $task['time_overtime'] = TimeHelper::secondsToHours($task['time_overtime']);
            unset($this->task_times_converted[$task_id]);
        }
    }

    /**
     * Init the tasks spent time the logic that tasks with
     * "timetagger_tag" should first reset their spent time
     * no matter what. Otherwise it should just convert
     * the original spent time into seconds.
     *
     * Also convert the other times to seconds.
     *
     * @param  array &$task
     */
    protected function taskTimesToSeconds(&$task)
    {
        $task_id = $task['id'] ?? -1;
        if (!array_key_exists($task_id, $this->task_times_converted)) {
            if (($task['timetagger_tags'] ?? '') != '') {
                $task['time_spent'] = 0;
                $task['time_estimated'] = TimeHelper::hoursToSeconds($task['time_estimated']);
                $task['time_remaining'] = TimesCalculator::calculateRemaining($task);
                $task['time_overtime'] = TimesCalculator::calculateOvertime(
                    $task['time_estimated'],
                    $task['time_spent'],
                    TimesCalculator::isDone($task)
                );
            } else {
                $task['time_spent'] = TimeHelper::hoursToSeconds(
                    $task['time_spent'] ?? 0.0
                );
                $task['time_estimated'] = TimeHelper::hoursToSeconds($task['time_estimated']);
                $task['time_remaining'] = TimeHelper::hoursToSeconds($task['time_remaining']);
                $task['time_overtime'] = TimeHelper::hoursToSeconds($task['time_overtime']);
            }
            $this->task_times_converted[$task_id] = &$task;
        }
    }

    /**
     * The functionality to overwrite a tasks times
     * with the event tracked times.
     *
     * $remaining_run will be used in the method
     * overwriteTimesForRemainingTasks(). Normally
     * during the normal distribution loop, which
     * will use overwriteTimesForTask(), the task
     * should not get more event time, if estimated
     * - spent is 0 (means that for the first run
     * tasks are being filled up to their max according
     * to estimated only first). But for the final run,
     * such tasks should get the remaining event time
     * on top.
     *
     * @param  array &$task
     * @param  string $timetagger_tags
     * @param  boolean $remaining_run
     */
    protected function overwriteTimes(&$task, $timetagger_tags, $remaining_run = false)
    {
        // generally tasks without "time_estimated" should not be touched
        if ($task['time_estimated'] == 0.0) {
            return;
        }

        // for each event, find matching tags and distribute
        foreach ($this->timetagger_fetcher->getEvents() as $event) {

            // available hours for this event
            $available = $event->getAvailable();
            if ($available <= 0) {
                continue;
            }

            $event_tags = $event->getTags() ?: [];

            if (!self::tagsMatch($timetagger_tags, $event_tags)) {
                continue;
            }

            $capacity = (
                TimesCalculator::isDone($task) && !$remaining_run ?
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

            // also adjust the other times now
            $task['time_remaining'] = TimesCalculator::calculateRemaining($task);
            $task['time_overtime'] = TimesCalculator::calculateOvertime(
                $task['time_estimated'],
                $task['time_spent'],
                TimesCalculator::isDone($task)
            );

            // additionally check if the task is running and should get
            // the "is_running" key
            if (!$this->gave_running && $event->isRunning()) {
                $task['is_running'] = true;
                $this->gave_running = true;
            }
        }
    }

    /**
     * This method should be called after the other method
     * overwriteSpentTimeForTask() was used inside a loop
     * for all tasks. This should have filled up the internal
     * attributes so that this method knows which tasks
     * now can get the remaining events time.
     */
    public function overwriteTimesForRemainingTasks()
    {
        foreach ($this->remaining_open_tasks as $timetagger_tags => &$task) {
            $this->overwriteTimes($task, $timetagger_tags, true);
            $this->taskTimesToHours($task);
        }
        foreach ($this->remaining_done_tasks as $timetagger_tags => &$task) {
            $this->overwriteTimes($task, $timetagger_tags, true);
            $this->taskTimesToHours($task);
        }
        // now convert the task times back from seconds to hours for the
        // tasks which were not in the remaining arrays from above
        foreach ($this->task_times_converted as &$task) {
            $this->taskTimesToHours($task);
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
    public function overwriteTimesForTask(&$task)
    {
        $this->taskTimesToSeconds($task);

        $timetagger_tags = self::getTimetaggerTagsSorted($task['timetagger_tags'] ?? '');
        if (empty($timetagger_tags)) {
            return;
        }
        if (TimesCalculator::isDone($task)) {
            // only add the first task from the whole cue, bascially.
            // so the first task having the exact timetagger tags
            // should be added here, only once.
            if (!array_key_exists($timetagger_tags, $this->remaining_done_tasks)) {
                $this->remaining_done_tasks[$timetagger_tags] = &$task;
            }
        } else {
            // again: only add the first task from the whole cue.
            if (!array_key_exists($timetagger_tags, $this->remaining_open_tasks)) {
                $this->remaining_open_tasks[$timetagger_tags] = &$task;
            }
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

        $this->overwriteTimes($task, $timetagger_tags);
    }
}
