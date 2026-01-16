<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

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

    public function __construct($timetagger_fetcher)
    {
        $this->timetagger_fetcher = $timetagger_fetcher;
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
            return true;
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
     * Keys may be missing; default to 0.
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
     * Overwrite the spent times for the given tasks.
     *
     * @param  array &$tasks
     */
    public function overwriteSpentTimesForTasks(&$tasks)
    {
        // TODO
        // times_spent, die bereits existiert, muss erst einmal auf 0.0 gesetzt
        // werden, falls es ein event dafür gibt! denn momentan addiert
        // der algorithmus stets zu times_spent!

        // get timetagger events (assume fetcher is already populated)
        $events = $this->timetagger_fetcher->getEvents();

        if (empty($events) || empty($tasks)) {
            // nothing to do
            return;
        }

        // for each event, find matching tasks and distribute
        foreach ($events as $event) {
            // available hours for this event
            $available = $event->getAvailable();
            if ($available <= 0) {
                continue;
            }

            $event_tags = $event->getTags() ?: [];

            // collect candidate task indices (in original order)
            // - and also convert times into seconds
            // - and set indicator if times_spent was reset before
            //   modification; otherwise the algorithm would always
            //   just add the allocated time to the original times_spent,
            //   but ultimately it should overwrite it completely.
            $candidates = [];
            foreach ($tasks as $i => &$task) {
                if (
                    isset($task['timetagger_tags'])
                    && self::tagsMatch($task['timetagger_tags'], $event_tags)
                ) {
                    // fill available candidates
                    $candidates[] = $i;

                    // change times from hours into seconds
                    // and reset times_spent
                    if (!($task['_timetagger_transcribing'] ?? false)) {
                        $task['_timetagger_transcribing'] = true;
                        $task['times_estimated'] = TimeHelper::hoursToSeconds($task['times_estimated']);
                        $task['times_spent'] = 0;
                    }
                }
            }
            unset($task);

            if (empty($candidates)) {
                // no matching tasks; skip this event
                continue;
            }

            // PHASE 1: fill matching DONE tasks up to times_estimated
            $modified_task_indices = []; // track which tasks we modified for fallback
            $non_done = [];  // for PHASE 2 later
            foreach ($candidates as $i) {
                if ($available <= 0) {
                    break;
                }

                if (!self::isTaskDone($tasks[$i])) {
                    $non_done[] = $i;
                    continue;
                }

                $capacity = max(0, $tasks[$i]['times_estimated'] - $tasks[$i]['times_spent']);
                if ($capacity <= 0) {
                    // nothing to fill for this done task
                    continue;
                }

                $alloc = min($capacity, $available);
                $tasks[$i]['times_spent'] += $alloc;
                $available -= $alloc;
                $event->distribute($alloc);
                $modified_task_indices[] = $i;
            }

            // PHASE 2: distribute remaining
            if ($available > 0) {
                if (!empty($non_done)) {
                    // simple strategy: assign all remaining to the first non-done candidate.
                    // (this can be modified to round-robin or proportional later maybe)
                    $target = $non_done[0];
                    $alloc = $available;
                    $tasks[$target]['times_spent'] += $alloc;
                    $available -= $alloc;
                    $event->distribute($alloc);
                    $modified_task_indices[] = $target;
                } else {
                    // all matching tasks are done.
                    // if we modified any task in phase 1, use the first such task as fallback.
                    // otherwise, fallback to the first candidate and assign the remaining there.
                    if (!empty($modified_task_indices)) {
                        $target = $modified_task_indices[0];
                    } else {
                        $target = $candidates[0];
                    }

                    $alloc = $available;
                    $tasks[$target]['times_spent'] += $alloc;
                    $available -= $alloc;
                    $event->distribute($alloc);
                    $modified_task_indices[] = $target;
                }
            }
        }

        // convert task times back to hours
        foreach ($tasks as &$task) {
            if (($task['_timetagger_transcribing'] ?? false)) {
                unset($task['_timetagger_transcribing']);
                $task['times_estimated'] = TimeHelper::secondsToHours($task['times_estimated']);
                $task['times_spent'] = TimeHelper::secondsToHours($task['times_spent']);
            }
        }
        unset($task);
    }
}
