<?php

namespace Kanboard\Plugin\WeekHelper\Action;

use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;

/**
 * Move task from column to other column on given day.
 *
 * @package action
 * @author  Manuel Senfft
 */
class TaskMoveFromColumnToColumnOnMonday extends Base
{
    /**
     * Get automatic action description
     *
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return t('Move the task in a specific swimland and a specific column to another column if on Monday.');
    }

    /**
     * Get the list of compatible events
     *
     * @access public
     * @return array
     */
    public function getCompatibleEvents()
    {
        return array(
            TaskModel::EVENT_DAILY_CRONJOB,
        );
    }

    /**
     * Get the required parameter for the action (defined by the user)
     *
     * @access public
     * @return array
     */
    public function getActionRequiredParameters()
    {
        return array(
            'swimlane_id' => t('Swimlane'),
            'src_column_id' => t('Source column'),
            'dest_column_id' => t('Destination column'),
        );
    }

    /**
     * Get the required parameter for the event
     *
     * @access public
     * @return string[]
     */
    public function getEventRequiredParameters()
    {
        return array('tasks');
    }

    /**
     * Execute the action
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool            True if the action was executed or false when not executed
     */
    public function doAction(array $data)
    {
        // exit immediately on "not Monday"
        if (date('D') !== 'Mon') {
            return true;
        }

        $results = [];

        foreach ($data['tasks'] as $task) {
            if (
                $task['swimlane_id'] == $this->getParam('swimlane_id')
                && $task['column_id'] == $this->getParam('src_column_id')
            ) {
                $results[] = $this->taskPositionModel->movePosition(
                    $task['project_id'],
                    $task['id'],
                    $this->getParam('dest_column_id'),
                    $task['position'],
                    $task['swimlane_id'],
                    true
                );
            }
        }

        return in_array(true, $results, true);
    }

    /**
     * Check if the event data meet the action condition
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool
     */
    public function hasRequiredCondition(array $data)
    {
        return count($data['tasks']) > 0;
    }
}