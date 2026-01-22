<?php

namespace Kanboard\Plugin\WeekHelper\Model;


/**
 * This class will get a task and its subtasks and do some calculation
 * to come up with estimated, spent, reamining and overtime values.
 */
class TimesCalculator
{
    /**
     * The config array.
     *
     * @var array
     **/
    var $config = [
        'non_time_mode_minutes' => 0
    ];

    /**
     * The internal estimated value.
     *
     * @var float
     **/
    var $estimated = null;

    /**
     * The internal overtime value.
     *
     * @var float
     **/
    var $overtime = null;

    /**
     * The internal remaining value.
     *
     * @var float
     **/
    var $remaining = null;

    /**
     * The internal spent value.
     *
     * @var float
     **/
    var $spent = null;

    /**
     * The tasks subtasks.
     *
     * @var array
     **/
    var $subtasks;

    /**
     * The subtasks estimated times in total.
     *
     * @var float
     **/
    var $subtasks_estimated = 0.0;

    /**
     * The subtasks spent times in total.
     *
     * @var float
     **/
    var $subtasks_spent = 0.0;

    /**
     * The task array.
     *
     * @var array
     **/
    var $task;

    /**
     * The TimetaggerTranscriber, which can overwrite
     * spent times for tasks.
     *
     * @var TimetaggerTranscriber
     **/
    var $timetagger_transcriber = null;

    /**
     * Instantiate the class with the given task and its subtasks.
     *
     * @param array  $task
     * @param array  $subtasks
     * @param array  $config
     * @param  TimetaggerTranscriber $timetagger_transcriber
     */
    public function __construct($task, $subtasks = [], $config = [], $timetagger_transcriber = null) {
        $this->initConfig($config);
        $this->task = $task;
        $this->subtasks = $subtasks;
        $this->initSubtasks();
        $this->timetagger_transcriber = $timetagger_transcriber;
    }

    /**
     * The calculation logic for a task or subtask.
     * It varies on the state of the task / subtask.
     *
     * @param  array $task_or_subtask
     * @return float
     */
    protected static function calculateRemaining($task_or_subtask)
    {
        $done = (
            // it's a task
            isset($task_or_subtask['is_active']) && $task_or_subtask['is_active'] == 0
        ) || (
            // it's a subtask
            isset($task_or_subtask['status']) && $task_or_subtask['status'] == 2
        );

        // if the subtask is done or the tasks is closed,
        // yet the spent time is below the estimated time,
        // only use the lower spent time as the estimated time then
        if ($done && $task_or_subtask['time_spent'] < $task_or_subtask['time_estimated']) {
            $tmp_estimated = $task_or_subtask['time_spent'];
        } else {
            $tmp_estimated = $task_or_subtask['time_estimated'];
        }
        return $tmp_estimated - $task_or_subtask['time_spent'];
    }

    /**
     * Get the remaining times from the given
     * subtasks in the array.
     *
     * @return float
     */
    protected function calculateRemainingFromSubtasks()
    {
        $out = 0.0;
        foreach ($this->subtasks as $subtask) {
            $tmp = self::calculateRemaining($subtask);

            // only add time as spending, as long as the spent time of the subtask
            // does not exceed the estimated time, so that in total
            // the remaining time will always represent the actual estimated
            // time throughout all subtasks
            if ($tmp > 0) {
                $out += $tmp;
            }
        }
        return $out;
    }

    /**
     * Calculate the spent from the subtasks IN non-time-mode.
     *
     * @return float
     */
    protected function calculateSpentNonTimeMode()
    {
        // add 'percentage' to the subtasks keys
        self::extendSubtasksWithPercentage($this->subtasks);

        // now get the full hours and calculate how many subtasks
        // did work on that already, while the status also means
        // if 1 == half of its percentage is done on the full
        // hours and 2 == its percentage is done fully.
        $full_hours = $this->getNonTimeModeMinutes() * $this->task['score'] / 60;
        $spent = 0.0;
        $time_override = 0.0;
        $has_override = false;
        foreach ($this->subtasks as $subtask) {
            // the LAST subtasks title can be a numeric and overwrite
            // the spent (negative value) or basically for the
            // remaining (positive value)
            if (is_numeric($subtask['title'])) {
                $has_override = true;
                if ($subtask['title'] > 0) {
                    if ($subtask['status'] == 1) {
                        $time_override = (float) $subtask['title'] / 2;
                    } elseif ($subtask['status'] == 0) {
                        $time_override = (float) $subtask['title'];
                    } elseif ($subtask['status'] == 2) {
                        $time_override = 0.0;
                    }
                } else {
                    $time_override = (float) $subtask['title'];
                }
            } else {
                // if this happens with the last subtask, it really should
                // not be overwritten.
                $has_override = false;
            }

            if ($subtask['status'] == 1 ) {
                // a begun subtask should stand for 50% of its time already ...
                $spent += $full_hours * ($subtask['percentage'] / 2);
            } elseif ($subtask['status'] == 2 ) {
                $spent += $full_hours * $subtask['percentage'];
            }

        }

        if ($has_override) {
            // override is positive: it stands for remaining
            if ($time_override >= 0) {
                $spent = $full_hours - $time_override;
                if ($spent < 0) {
                    $spent = 0;
                }

            // override is negative: it stands for spent
            } elseif ($time_override < 0) {
                $spent = $full_hours - ($full_hours - ($time_override * -1));
            }
        }

        return $spent;
    }

    /**
     * Extend the given subtasks array and add 'percentage'
     * to their keys. The logic is basically that a subtasks
     * title can contain a percentage string like "30 %" or "30%"
     * which would tell the system how much this subtask occupies
     * in time of the whole.
     *
     * exmaple 1:
     * 5 subtasks with no percentages. this means that every subtask
     * should have 20% automatically.
     *
     * example 2:
     * 5 subtasks with 1 with 40% and 1 with 10%. this means that
     * these two already occupy 50% of all subtasks. means that
     * the remaining 3 subtasks have to share 50%, means that one
     * subtask of them is 16,6% of the whole subtasks sum.
     *
     * @param  array &$subtasks
     */
    protected static function extendSubtasksWithPercentage(&$subtasks)
    {
        $countWithout = 0;
        $percentRemaining = 1.0;

        // first run: parse, set known percentages, count unknowns
        foreach ($subtasks as $k => $s) {
            $p = self::percentFromString($s['title']);
            if ($p != -1) {
                $subtasks[$k]['percentage'] = $p;
                $percentRemaining -= $p;
            } else {
                // mark otherwise, assigning later, count increasing
                $subtasks[$k]['percentage'] = null;
                $countWithout++;
            }
        }

        if ($countWithout === 0) {
            return;
        }

        // rounding fixing
        if ($percentRemaining <= 0.0) {
            $fill = 0.0;
        } else {
            $fill = $percentRemaining / $countWithout;
        }

        // fill in subtasks without given percentage
        foreach ($subtasks as $k => $s) {
            if ($s['percentage'] === null) {
                $subtasks[$k]['percentage'] = $fill;
            }
        }
    }

    /**
     * Get the internal config for the given key.
     *
     * @param  string $key
     * @return string|integer|null
     */
    protected function getConfig($key)
    {
        return $this->config[$key] ?? null;
    }

    /**
     * Return the estimated value. Initialize it first,
     * if needed.
     *
     * @return float
     */
    public function getEstimated()
    {
        if (is_null($this->estimated)) {
            $this->initEstimated();
        }
        return $this->estimated;
    }

    /**
     * Get the config value for the non-time-mode minutes,
     * but just make a call once; while the original value
     * is still -1.
     */
    protected function getNonTimeModeMinutes()
    {
        return $this->getConfig('non_time_mode_minutes');
    }

    /**
     * Get the bool if the non-time-mode is enabled or not.
     */
    protected function getNonTimeModeEnabled()
    {
        return $this->getNonTimeModeMinutes() > 0;
    }

    /**
     * Get overtime time, initialize it first, if needed.
     *
     * @return float
     */
    public function getOvertime()
    {
        if (is_null($this->overtime)) {
            $this->initOvertime();
        }
        return $this->overtime;
    }

    /**
     * Get remaining time, initialize it first, if needed.
     *
     * @return float
     */
    public function getRemaining()
    {
        if (is_null($this->remaining)) {
            $this->initRemaining();
        }
        return $this->remaining;
    }

    /**
     * Get spent time, initialize it first, if needed.
     *
     * @return float
     */
    public function getSpent()
    {
        if (is_null($this->spent)) {
            $this->initSpent();
        }
        return $this->spent;
    }

    /**
     * Init the inetrnal config with the given one.
     *
     * @param  array $config
     */
    public function initConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Calculate the estimated time.
     */
    protected function initEstimated()
    {
        if ($this->getNonTimeModeEnabled()) {
            if (!array_key_exists('score', $this->task)) {
                $this->task['score'] = 0;
            }
            $this->estimated = (float) $this->getNonTimeModeMinutes() * $this->task['score'] / 60;
            $this->task['time_estimated'] = $this->estimated;
        } else {
            if (empty($this->subtasks)) {
                $estimated = (float) $this->task['time_estimated'];
            } else {
                $estimated = $this->subtasks_estimated;
            }
            $this->task['time_estimated'] = $estimated;
            $this->estimated = $estimated;
        }
    }

    /**
     * Calculate the overtime.
     *
     * The logic is that this value depends on the state of the
     * overall task. E.g. if the tasks is not done yet, the over
     * time cannot be negative (which would mean that the task
     * was done faster then estimated).
     */
    protected function initOvertime()
    {
        $overtime = $this->getSpent() - $this->getEstimated();
        if ($overtime < 0.0 && !$this->isDone()) {
            $overtime = 0.0;
        }
        $this->task['time_overtime'] = $overtime;
        $this->overtime = $overtime;
    }

    /**
     * Calculate the remaining time.
     */
    protected function initRemaining()
    {
        $remaining = $this->getEstimated() - $this->getSpent();
        $remaining = $remaining < 0 ? 0 : $remaining;
        $this->task['time_remaining'] = $remaining;
        $this->remaining = $remaining;
    }

    /**
     * Calculate the spent time.
     */
    protected function initSpent()
    {
        // non time mode
        if ($this->getNonTimeModeEnabled()) {
            $spent = $this->calculateSpentNonTimeMode();

        // from subtasks
        } elseif (!$this->getNonTimeModeEnabled() && !empty($this->subtasks)) {
            $spent = $this->subtasks_spent;

        // use the task times itself
        } else {
            // just in case: cast the given value as float.
            $spent = (float) $this->task['time_spent'];
        }

        $this->task['time_spent'] = $spent;
        $this->spent = $spent;
    }

    /**
     * Init certain numbers form the subtasks, which might be used
     * later.
     */
    protected function initSubtasks()
    {
        if (!array_key_exists('open_subtasks', $this->task)) {
            $this->task['open_subtasks'] = 0;
        }
        foreach ($this->subtasks as $subtask) {
            $this->task['open_subtasks'] += $subtask['status'] != 2 ? 1 : 0;
            $this->subtasks_estimated += $subtask['time_estimated'];
            $this->subtasks_spent += $subtask['time_spent'];
        }
    }

    /**
     * The logic if a task is considered to be done or not.
     *
     * @return boolean
     */
    public function isDone()
    {
        // subtasks exist: none has to be open still
        if (!empty($this->subtasks)) {
            return $this->task['open_subtasks'] == 0;

        // no subtasks exist: spent has to be >= estimated
        } else {
            return $this->getSpent() >= $this->getEstimated();
        }
    }

    /**
     * Get a percentage float from the given string. E.g.
     * maybe it's a subtask with the title "30% todo".
     * In that case this function would return 0.3.
     * Otherwise it returns -1.
     *
     * @param  string $string
     * @return float
     */
    protected static function percentFromString($string = '')
    {
        if (!is_string($string) || $string === '') {
            return -1.0;
        }

        // search for a number followed by an optional whitespace and '%'
        if (preg_match('/([+-]?\d+(?:[.,]\d+)?)\s*%/u', $string, $m)) {
            // normalize decimal to a dot
            $num = str_replace(',', '.', $m[1]);
            // try to convert to a float
            if (is_numeric($num)) {
                $val = (float) $num;
                return $val / 100.0;
            }
        }
        return -1.0;
    }

    /**
     * Update the given task with the internal task. This basically
     * will just overwrite the given array with the internal array.
     *
     * @param  array &$task
     * @return array
     */
    public function updateTask(&$task)
    {
        $task = $this->task;
        return $this->task;
    }
}
