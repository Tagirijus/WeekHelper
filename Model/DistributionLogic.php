<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
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
     * This array holds the project limits for each project
     * by its ID and also by the overall level:
     *
     *  [
     *      level_1 => [
     *          project_id => ProjectLimits,
     *          ...
     *      ],
     *      level_2 => [
     *          project_id => ProjectLimits,
     *          ...
     *      ],
     *      ...
     *  ]
     *
     * @var array
     **/
    var $project_limits_by_level = [];


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
