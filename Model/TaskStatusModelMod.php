<?php

// namespace Kanboard\Model;
namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Core\Base;

/**
 * Task Status
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class TaskStatusModelMod extends Base
{
    /**
     * Return true if the task is closed
     *
     * @access public
     * @param  integer    $task_id     Task id
     * @return boolean
     */
    public function isClosed($task_id)
    {
        return $this->checkStatus($task_id, \Kanboard\Model\TaskModel::STATUS_CLOSED);
    }

    /**
     * Return true if the task is open
     *
     * @access public
     * @param  integer    $task_id     Task id
     * @return boolean
     */
    public function isOpen($task_id)
    {
        return $this->checkStatus($task_id, \Kanboard\Model\TaskModel::STATUS_OPEN);
    }

    /**
     * Mark a task closed
     *
     * @access public
     * @param  integer   $task_id   Task id
     * @return boolean
     */
    public function close($task_id)
    {
        $this->subtaskStatusModel->closeAll($task_id);
        return $this->changeStatus($task_id, \Kanboard\Model\TaskModel::STATUS_CLOSED, time(), \Kanboard\Model\TaskModel::EVENT_CLOSE);
    }

    /**
     * Mark a task open
     *
     * @access public
     * @param  integer   $task_id   Task id
     * @return boolean
     */
    public function open($task_id)
    {
        return $this->changeStatus($task_id, \Kanboard\Model\TaskModel::STATUS_OPEN, 0, \Kanboard\Model\TaskModel::EVENT_OPEN);
    }

    /**
     * Close multiple tasks
     *
     * @access public
     * @param  array   $task_ids
     */
    public function closeMultipleTasks(array $task_ids)
    {
        foreach ($task_ids as $task_id) {
            $this->close($task_id);
        }
    }

    /**
     * Close all tasks within a column/swimlane
     *
     * @access public
     * @param  integer $swimlane_id
     * @param  integer $column_id
     */
    public function closeTasksBySwimlaneAndColumn($swimlane_id, $column_id)
    {
        $task_ids = $this->db
            ->table(\Kanboard\Model\TaskModel::TABLE)
            ->eq('swimlane_id', $swimlane_id)
            ->eq('column_id', $column_id)
            ->eq(\Kanboard\Model\TaskModel::TABLE.'.is_active', \Kanboard\Model\TaskModel::STATUS_OPEN)
            ->findAllByColumn('id');

        $this->closeMultipleTasks($task_ids);
    }

    /**
     * Common method to change the status of task
     *
     * @access private
     * @param  integer   $task_id             Task id
     * @param  integer   $status              Task status
     * @param  integer   $date_completed      Timestamp
     * @param  string    $event_name          Event name
     * @return boolean
     */
    private function changeStatus($task_id, $status, $date_completed, $event_name)
    {
        if (! $this->taskFinderModel->exists($task_id)) {
            return false;
        }

        // check if the task has subtasks
        $subtasks = $this->helper->hoursViewHelper->getSubtasksByTaskId($task_id);
        if (!empty($subtasks)) {
            // use ignore subtask feature of HoursView on opening
            if ($status == \Kanboard\Model\TaskModel::STATUS_OPEN) {
                $this->subtaskTimeTrackingModel->updateTaskTimeTracking($task_id, true);

            // otherwise "undo" the ignoring
            } elseif ($status == \Kanboard\Model\TaskModel::STATUS_CLOSED) {
                $this->subtaskTimeTrackingModel->updateTaskTimeTracking($task_id, false);
            }
        }

        $result = $this->db
                        ->table(\Kanboard\Model\TaskModel::TABLE)
                        ->eq('id', $task_id)
                        ->update(array(
                            'is_active' => $status,
                            'date_completed' => $date_completed,
                            'date_modification' => time(),
                        ));

        if ($result) {
            $this->queueManager->push($this->taskEventJob->withParams($task_id, array($event_name)));
        }

        return $result;
    }

    /**
     * Check the status of a task
     *
     * @access private
     * @param  integer   $task_id   Task id
     * @param  integer   $status    Task status
     * @return boolean
     */
    private function checkStatus($task_id, $status)
    {
        return $this->db
                    ->table(\Kanboard\Model\TaskModel::TABLE)
                    ->eq('id', $task_id)
                    ->eq('is_active', $status)
                    ->exists();
    }
}
