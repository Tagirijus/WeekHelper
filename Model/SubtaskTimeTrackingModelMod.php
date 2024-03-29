<?php

// namespace Kanboard\Model;
namespace Kanboard\Plugin\WeekHelper\Model;

use DateTime;
use Kanboard\Core\Base;

/**
 * Subtask time tracking
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class SubtaskTimeTrackingModelMod extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'subtask_time_tracking';

    /**
     * Get query to check if a timer is started for the given user and subtask
     *
     * @access public
     * @param  integer    $user_id   User id
     * @return string
     */
    public function getTimerQuery($user_id)
    {
        $sql = $this->db
                    ->table(self::TABLE)
                    ->columns('start')
                    ->eq($this->db->escapeIdentifier('user_id',self::TABLE), $user_id)
                    ->eq($this->db->escapeIdentifier('end',self::TABLE), 0)
                    ->eq($this->db->escapeIdentifier('subtask_id',self::TABLE), \Kanboard\Model\SubtaskModel::TABLE.'.id')
                    ->limit(1)
                    ->buildSelectQuery();
        // need to interpolate values into the SQL text for use as a subquery
        // in \Kanboard\Model\SubtaskModel::getQuery()
        $sql = substr_replace($sql, $user_id, strpos($sql, '?'), 1);
        $sql = substr_replace($sql, 0, strpos($sql, '?'), 1);
        $sql = substr_replace($sql, \Kanboard\Model\SubtaskModel::TABLE.'.id', strpos($sql, '?'), 1);
        return $sql;
    }

    /**
     * Get query for user timesheet (pagination)
     *
     * @access public
     * @param  integer    $user_id   User id
     * @return \PicoDb\Table
     */
    public function getUserQuery($user_id)
    {
        return $this->db
                    ->table(self::TABLE)
                    ->columns(
                        self::TABLE.'.id',
                        self::TABLE.'.subtask_id',
                        self::TABLE.'.end',
                        self::TABLE.'.start',
                        self::TABLE.'.time_spent',
                        \Kanboard\Model\SubtaskModel::TABLE.'.task_id',
                        \Kanboard\Model\SubtaskModel::TABLE.'.title AS subtask_title',
                        \Kanboard\Model\TaskModel::TABLE.'.title AS task_title',
                        \Kanboard\Model\TaskModel::TABLE.'.project_id',
                        \Kanboard\Model\TaskModel::TABLE.'.color_id'
                    )
                    ->join(\Kanboard\Model\SubtaskModel::TABLE, 'id', 'subtask_id')
                    ->join(\Kanboard\Model\TaskModel::TABLE, 'id', 'task_id', \Kanboard\Model\SubtaskModel::TABLE)
                    ->eq(self::TABLE.'.user_id', $user_id);
    }

    /**
     * Get query for task timesheet (pagination)
     *
     * @access public
     * @param  integer    $task_id    Task id
     * @return \PicoDb\Table
     */
    public function getTaskQuery($task_id)
    {
        return $this->db
                    ->table(self::TABLE)
                    ->columns(
                        self::TABLE.'.id',
                        self::TABLE.'.subtask_id',
                        self::TABLE.'.end',
                        self::TABLE.'.start',
                        self::TABLE.'.time_spent',
                        self::TABLE.'.user_id',
                        \Kanboard\Model\SubtaskModel::TABLE.'.task_id',
                        \Kanboard\Model\SubtaskModel::TABLE.'.title AS subtask_title',
                        \Kanboard\Model\TaskModel::TABLE.'.project_id',
                        \Kanboard\Model\UserModel::TABLE.'.username',
                        \Kanboard\Model\UserModel::TABLE.'.name AS user_fullname'
                    )
                    ->join(\Kanboard\Model\SubtaskModel::TABLE, 'id', 'subtask_id')
                    ->join(\Kanboard\Model\TaskModel::TABLE, 'id', 'task_id', \Kanboard\Model\SubtaskModel::TABLE)
                    ->join(\Kanboard\Model\UserModel::TABLE, 'id', 'user_id', self::TABLE)
                    ->eq(\Kanboard\Model\TaskModel::TABLE.'.id', $task_id);
    }

    /**
     * Get all recorded time slots for a given user
     *
     * @access public
     * @param  integer    $user_id       User id
     * @return array
     */
    public function getUserTimesheet($user_id)
    {
        return $this->db
                    ->table(self::TABLE)
                    ->eq('user_id', $user_id)
                    ->findAll();
    }

    /**
     * Return true if a timer is started for this use and subtask
     *
     * @access public
     * @param  integer  $subtask_id
     * @param  integer  $user_id
     * @return boolean
     */
    public function hasTimer($subtask_id, $user_id)
    {
        return $this->db->table(self::TABLE)->eq('subtask_id', $subtask_id)->eq('user_id', $user_id)->eq('end', 0)->exists();
    }

    /**
     * Start or stop timer according to subtask status
     *
     * @access public
     * @param  integer $subtask_id
     * @param  integer $user_id
     * @param  integer $status
     * @return boolean
     */
    public function toggleTimer($subtask_id, $user_id, $status)
    {
        if ($this->configModel->get('subtask_time_tracking') == 1) {
            if ($status == \Kanboard\Model\SubtaskModel::STATUS_INPROGRESS) {
                return $this->subtaskTimeTrackingModel->logStartTime($subtask_id, $user_id);
            } elseif ($status == \Kanboard\Model\SubtaskModel::STATUS_DONE) {
                return $this->subtaskTimeTrackingModel->logEndTime($subtask_id, $user_id);
            }
        }

        return false;
    }

    /**
     * Log start time
     *
     * @access public
     * @param  integer   $subtask_id
     * @param  integer   $user_id
     * @return boolean
     */
    public function logStartTime($subtask_id, $user_id)
    {
        return
            ! $this->hasTimer($subtask_id, $user_id) &&
            $this->db
                ->table(self::TABLE)
                ->insert(array('subtask_id' => $subtask_id, 'user_id' => $user_id, 'start' => time(), 'end' => 0));
    }

    /**
     * Log end time
     *
     * @access public
     * @param  integer   $subtask_id
     * @param  integer   $user_id
     * @return boolean
     */
    public function logEndTime($subtask_id, $user_id)
    {
        $time_spent = $this->getTimeSpent($subtask_id, $user_id);

        if ($time_spent > 0) {
            $this->updateSubtaskTimeSpent($subtask_id, $time_spent);
        }

        return $this->db
                    ->table(self::TABLE)
                    ->eq('subtask_id', $subtask_id)
                    ->eq('user_id', $user_id)
                    ->eq('end', 0)
                    ->update(array(
                        'end' => time(),
                        'time_spent' => $time_spent,
                    ));
    }

    /**
     * Calculate the time spent when the clock is stopped
     *
     * @access public
     * @param  integer   $subtask_id
     * @param  integer   $user_id
     * @return float
     */
    public function getTimeSpent($subtask_id, $user_id)
    {
        $hook = 'model:subtask-time-tracking:calculate:time-spent';
        $start_time = $this->db
            ->table(self::TABLE)
            ->eq('subtask_id', $subtask_id)
            ->eq('user_id', $user_id)
            ->eq('end', 0)
            ->findOneColumn('start');

        if (empty($start_time)) {
            return 0;
        }

        $end = new DateTime;
        $start = new DateTime;
        $start->setTimestamp($start_time);

        if ($this->hook->exists($hook)) {
            return $this->hook->first($hook, array(
                'user_id' => $user_id,
                'start' => $start,
                'end' => $end,
            ));
        }

        return $this->dateParser->getHours($start, $end);
    }

    /**
     * Update subtask time spent
     *
     * @access public
     * @param  integer   $subtask_id
     * @param  float     $time_spent
     * @return bool
     */
    public function updateSubtaskTimeSpent($subtask_id, $time_spent)
    {
        $subtask = $this->subtaskModel->getById($subtask_id);

        return $this->subtaskModel->update(array(
            'id' => $subtask['id'],
            'time_spent' => $subtask['time_spent'] + $time_spent,
            'task_id' => $subtask['task_id'],
        ), false);
    }

    /**
     * Update task time tracking based on subtasks time tracking
     *
     * @access public
     * @param  integer   $task_id    Task id
     * @param  bool      $use_ignores
     * @return bool
     */
    public function updateTaskTimeTracking($task_id, $use_ignores = true)
    {
        $values = $this->calculateSubtaskTime($task_id, $use_ignores);

        return $this->db
                    ->table(\Kanboard\Model\TaskModel::TABLE)
                    ->eq('id', $task_id)
                    ->update($values);
    }

    /**
     * Sum time spent and time estimated for all subtasks
     *
     * @access public
     * @param  integer   $task_id    Task id
     * @param  bool      $use_ignores
     * @return array
     */
    public function calculateSubtaskTime($task_id, $use_ignores = true)
    {
        if ($use_ignores) {
            $ignore_subtask_titles = $this->helper->hoursViewHelper->getIgnoredSubtaskTitles();
        } else {
            $ignore_subtask_titles = [];
        }
        $tmpQuery = $this->db
                    ->table(\Kanboard\Model\SubtaskModel::TABLE)
                    ->eq('task_id', $task_id)
                    ->columns(
                        'SUM(time_spent) AS time_spent',
                        'SUM(time_estimated) AS time_estimated'
                    );
        if (!empty($ignore_subtask_titles)) {
            foreach ($ignore_subtask_titles as $ignore_title_substring) {
                $tmpQuery->addCondition('`title` NOT LIKE \'%' . $ignore_title_substring . '%\'');
            }
        }
        return $tmpQuery->findOne();
    }
}
