<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
use Kanboard\Plugin\WeekHelper\Model\ProjectQuotaAll;
use Kanboard\Plugin\WeekHelper\Model\TasksPlan;
use Kanboard\Plugin\WeekHelper\Model\TimeSlotsDay;
use Kanboard\Plugin\WeekHelper\Model\TimePoint;


class DistributionLogic
{
    /**
     * The global tasks plan instance.
     *
     * @var TasksPlan
     **/
    var $tasks_plan;

    /**
     * The internal array holding the generated pseudo
     * tasks from the blocking config, which stand for
     * pseudo tasks, so that it is possible to show
     * the blocking timespans with their title in the
     * plan as well.
     *
     * @var array
     **/
    var $blocking_pseudo_tasks = [
        'mon' => [],
        'tue' => [],
        'wed' => [],
        'thu' => [],
        'fri' => [],
        'sat' => [],
        'sun' => [],
        'overflow' => [],
    ];

    /**
     * An array with all the TimeSlotsDay instances, which
     * are capable of holding slot capacities.
     * The last ("overflow") instance is basically a day
     * with many hours of limit. So tasks, which do not
     * fit into the week, will fit on this "special day".
     *
     * @var array
     **/
    var $time_slots_days = [
        'mon' => null,
        'tue' => null,
        'wed' => null,
        'thu' => null,
        'fri' => null,
        'sat' => null,
        'sun' => null,
        'overflow' => null,
    ];

    /**
     * Initialize the class with its attributes.
     *
     * @param array  $time_slots_config
     */
    public function __construct($time_slots_config = [
        'mon' => '',
        'tue' => '',
        'wed' => '',
        'thu' => '',
        'fri' => '',
        'sat' => '',
        'sun' => '',
        'min_slot_length' => 0,
        'non_time_mode_minutes' => 0,
        ]
    )
    {
        $this->parseTimeSlots($time_slots_config);
        $this->initTasksPlans($time_slots_config);
    }

    /**
     * Distirbute the tasks among the defined internal time slots.
     * This method simply iters through the internal TimeSlotsDay
     * instances and use them to plan tasks with the TasksPlan
     * method, which will handle the final logic.
     *
     * @param  array $tasks
     */
    public function distributeTasks($tasks)
    {
        foreach ($this->time_slots_days as $time_slots_day) {
            foreach ($tasks as $task) {
                $this->tasks_plan->planTask($task, $time_slots_day);
            }
        }
    }

    /**
     * Deplete the ProjectQuota based on the given TasksTimesPreparer,
     * the given level string (for fetching the times_spent for the
     * project and depending on the level) and all while considering
     * the TimePoint. TimePoint is for "everything before that is
     * supposed to be filled with spent_time first, after that it
     * should happen from end of week until TimePoint, backwards".
     *
     * Also this technically will not really deplete the TasksPlan
     * ProjectQuotaAll, but basically will override it.
     *
     * INFO:
     * $time_point here is non-precise when it comes to knowing how
     * much for wich project was spent already on that day before this
     * time point. Because: maybe before that TimePoint there are slots,
     * but none for which the project normally could have been planned
     * tasks for. Since this info only is possible in combination with
     * individual tasks, this info cannot really be fetched here. So
     * my agreement for now is to just let a TimeSlotsDay will return
     * generally how much time before the TimePoint could have been spent
     * regardless of the slot-condition, which is used when planning tasks.
     * This can also result in the scenario that two projects with
     * worked-in-advance-time could see such time slot time before the
     * timepoint as "spent time was done in this slot". So 1h of
     * slot could have been used for e.g. multiple 1h of worked-in-advance
     * time per project (e.g. I worked in advance for two projects could
     * mean that 2h in this 1h time slot was allegedly spent). This is
     * surreal, but something I really do not want to solve right now, since
     * it makes my head really hurt. I might have come up with an overall
     * and completely different business logic for the whole automatic
     * planning system . . .
     *
     * @param  TasksTimesPreparer $tasks_times_preparer
     * @param  string $level
     * @param  TimePoint $time_point
     */
    public function depleteProjectQuota($tasks_times_preparer, $level, $time_point)
    {
        $quota = $this->initProjectQuotaFromTasksTimesPreparer($tasks_times_preparer);
    }

    /**
     * Create a ProjectQuotaAll depending on the given TasksTimesPreparer and the
     * internal TimeSlotsDay instances. This will first basically get the
     * project limits from the TasksTimesPreparer and check against the actual
     * available time for the day from the TimeSlotsDay and modify the quota
     * if needed.
     *
     * @param  TasksTimesPreparer $tasks_times_preparer
     * @return ProjectQuotaAll
     */
    protected function initProjectQuotaFromTasksTimesPreparer($tasks_times_preparer)
    {
        $quota = new ProjectQuotaAll();
        foreach ($tasks_times_preparer->getProjectLimits() as $project_id => $limits) {
            $quota->initProjectQuota($project_id);
            foreach ($limits as $day => $minutes) {
                $available_length = $this->time_slots_days[$day]->getLength(true);
                $quota->setQuotaByProjectIdAndDay(
                    $project_id, $day,
                    $minutes <= $available_length ? $minutes : $available_length
                );
            }
        }
        return $quota;
    }

    /**
     * Initialize the internal TasksPlan instances.
     *
     * @param array  $time_slots_config
     */
    protected function initTasksPlans($time_slots_config)
    {
        $this->tasks_plan = new TasksPlan(
            $time_slots_config['min_slot_length'],
            $time_slots_config['non_time_mode_minutes']
        );
    }

    /**
     * Parse the raw strings from the config for the time slots
     * and create usable data from it (maybe objects or so).
     *
     * @param  array $time_slots_config
     */
    public function parseTimeSlots($time_slots_config)
    {
        $this->time_slots_days['mon'] = new TimeSlotsDay($time_slots_config['mon'], 'mon');
        $this->time_slots_days['tue'] = new TimeSlotsDay($time_slots_config['tue'], 'tue');
        $this->time_slots_days['wed'] = new TimeSlotsDay($time_slots_config['wed'], 'wed');
        $this->time_slots_days['thu'] = new TimeSlotsDay($time_slots_config['thu'], 'thu');
        $this->time_slots_days['fri'] = new TimeSlotsDay($time_slots_config['fri'], 'fri');
        $this->time_slots_days['sat'] = new TimeSlotsDay($time_slots_config['sat'], 'sat');
        $this->time_slots_days['sun'] = new TimeSlotsDay($time_slots_config['sun'], 'sun');
        $this->time_slots_days['overflow'] = new TimeSlotsDay('0:00-100:00', 'overflow');
    }

    /**
     * Return the spicey task plan finally!
     *
     * @return TasksPlan
     */
    public function getTasksPlan()
    {
        return $this->tasks_plan;
    }

    /**
     * Deplete all time slots until the given TimePoint. This
     * basically could, for example, deplete all the time slots
     * on all days before "now". But for testing purposes this
     * can also be any other TimePoint.
     *
     * @param  TimePoint $time_point
     */
    public function depleteUntilTimePoint($time_point)
    {
        // iter through all time_slots_days values,
        // until the given TimePoint difference in days is positive
        foreach ($this->time_slots_days as &$time_slots_day) {
            $day_diff = $time_slots_day->dayDiffFromTimePoint($time_point);

            // basically stop completely, if the given time point is
            // any day after the TimeSlotsDay day itself.
            if ($day_diff < 0) {
                return;

            // deplete whole days, if it is before the day
            } elseif ($day_diff > 0) {
                $time_slots_day->deplete();

            // same day now; deplete the given day TO the given
            // TimePoint start
            } elseif ($day_diff == 0) {
                $time_slots_day->depleteByTimePoint($time_point);
            }
        }
    }

    /**
     * Will create a TimePoint automatically internally for "now"
     * and deplete the week until this point.
     */
    public function depleteUntilNow()
    {
        $this->depleteUntilTimePoint(new TimePoint());
    }

    /**
     * Deplete the time slots by a given array of TimeSpan instances,
     * where the keys of the array are the day:
     *     [
     *         'mon' => [
     *             ['timespan' => TimeSpan, 'title' => 'abc'],
     *             ...
     *         ],
     *         'tue' => ...,
     *         ...
     *     ]
     *
     * Returns true or false depending on the success.
     *
     * @param  array $time_spans_by_day
     * @return boolean
     */
    public function depleteByTimeSpans($time_spans_by_day)
    {
        $success = [];
        foreach ($time_spans_by_day as $day => $time_spans) {
            if (array_key_exists($day, $this->time_slots_days)) {
                foreach ($time_spans as $time_span) {
                    $success[] = $this->time_slots_days[$day]->depleteByTimeSpan($time_span['timespan']);
                }
            }
        }
        return !in_array(false, $success);
    }

    /**
     * Wrapper for depleteByTimeSpans, except that the parameter
     * can be the config string, which will be parsed internally.
     *
     * If $time_point is given, the blocking pseudo tasks will have
     * their start, spent and remaining time set by the actual time.
     * This way, if the actual time is inside or after such pseudo
     * task, it will be done automatically. By default this value
     * is null, thus no TimePoint instance, which will deactivate
     * this automatic calculation.
     *
     * @param  string $blocking_config
     * @param  TimePoint|null $time_point
     * @return boolean
     */
    public function depleteByTimeSpansConfig($blocking_config, $time_point = null)
    {
        [
            $blocking_timespans,
            $this->blocking_pseudo_tasks
        ] = self::blockingConfigParser($blocking_config, $time_point);
        return $this->depleteByTimeSpans(
            $blocking_timespans
        );
    }

    /**
     * Basically a wrapper for depleteByTimeSpansConfig, where the
     * TimePoint for "now" will be generated automatically.
     *
     * @param  string $blocking_config
     * @return boolean
     */
    public function depleteByTimeSpansConfigUntilNow($blocking_config)
    {
        return $this->depleteByTimeSpansConfig(
            $blocking_config,
            new TimePoint()
        );
    }

    /**
     * Convert the config blocking string into an array
     * with days as keys and an array of TimeSpan instances
     * as values.
     *
     * Will return two arrays in an array:
     *     [
     *         blocking_timespans: array,
     *         pseudo_tasks: array
     *     ]
     *
     * If $time_point is given, the blocking pseudo tasks will have
     * their start, spent and remaining time set by the actual time.
     * This way, if the actual time is inside or after such pseudo
     * task, it will be done automatically. By default this value
     * is null, thus no TimePoint instance, which will deactivate
     * this automatic calculation.
     *
     * @param  string $blocking_config
     * @param  TimePoint|null $time_point
     * @return array
     */
    public static function blockingConfigParser($blocking_config, $time_point = null)
    {
        // this one is needed for depleting
        $blocking_timespans = [
            'mon' => [],
            'tue' => [],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
        ];
        $pseudo_tasks = [
            'mon' => [],
            'tue' => [],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
            'overflow' => [],
        ];
        $lines = explode("\n", $blocking_config ?? '');
        foreach ($lines as &$line) {

            $line = trim($line);

            // splitting initial config string into times and project_type
            $parts = preg_split('/\s+/', $line, 3);
            $day = $parts[0];
            if (count($parts) > 1) {
                $times = $parts[1];
            } else {
                return [$blocking_timespans, $pseudo_tasks];
            }
            if (count($parts) > 2) {
                $title = $parts[2];
            } else {
                $title = '';
            }

            // now splitting times into start and end
            $times_parts = preg_split('/\-/', $times);
            if (count($times_parts) == 2) {
                $start = TimeHelper::readableToMinutes($times_parts[0]);
                $end = TimeHelper::readableToMinutes($times_parts[1]);
            } else {
                $start = 0;
                $end = 0;
            }

            // alter the blocking pseudo tasks length by the
            // given timepoint
            $spent = 0;
            if (!is_null($time_point)) {
                // make the task fully done, since it is in the past
                if (
                    TimeHelper::diffOfWeekDays($time_point->getDay(), $day) < 0
                    || (
                        TimeHelper::diffOfWeekDays($day, $time_point->getDay()) == 0
                        && $end <= $time_point->getTime()
                    )
                ) {
                    $spent = $end - $start;
                    $start = $end;

                // make a part of the task done, since the TimePoint is inside
                // the tasks time span
                } elseif (
                    TimeHelper::diffOfWeekDays($time_point->getDay(), $day) == 0
                    && $end > $time_point->getTime()
                    && $start < $time_point->getTime()
                ) {
                    $spent = $time_point->getTime() - $start;
                    $start = $time_point->getTime();
                }
            }

            // add a time span to blocking_timespans array
            $blocking_timespans[$day][] = [
                'timespan' => new TimeSpan($start, $end),
                'title' => $title
            ];

            // add a pseudo task; but only if it has a length at all!
            if ($end - $start > 0) {
                $pseudo_tasks[$day][] = [
                    'task' => [
                        'title' => $title,
                        'project_name' => 'Blocking Dates',
                        'project_alias' => '',
                        'is_blocking' => true
                    ],
                    'start' => $start,
                    'end' => $end,
                    'length' => $end - $start,
                    'spent' => $spent,
                    'remaining' => $end - $start,

                ];
            }
        }
        return [$blocking_timespans, $pseudo_tasks];
    }

    /**
     * Return the internal blocking pseudo tasks.
     *
     * @return array
     */
    public function getBlockingPseudoTasks()
    {
        return $this->blocking_pseudo_tasks;
    }
}
