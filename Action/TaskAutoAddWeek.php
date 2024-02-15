<?php

namespace Kanboard\Plugin\WeekHelper\Action;

use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;

/**
 * Rename Task Title
 *
 * @package action
 * @author  Frederic Guillot
 */
class TaskAutoAddWeek extends Base
{
    /**
     * Get automatic action description
     *
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return t('Change a tasks title where the week-pattern, if it exists, will be added one week');
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
            TaskModel::EVENT_MOVE_COLUMN,
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
            'column_id' => t('Column'),
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
        return array(
            'task_id',
            'src_column_id',
        );
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
        // get / create base strings
        $title = $data['task']['title'];
        $newTitle = $this->helper->weekHelperHelper->addOneWeekToGivenTitle($title);

        // not the actual action, but I want it for my workflow:
        // the "[DUPICATE]" shall be removed as well
        $newTitle = str_replace(
            ['[DUPLICATE]', '[DUPLIKAT]'],
            ['', ''],
            $newTitle
        );

        // trim whitespace around title;
        // otherwise I could also replace "[DUPICATE] "
        // (including the whitespace) by ""; but I want it
        // this way; in case the ending has whitespace as well
        // by accident
        $newTitle = trim($newTitle);

        // replace the title finally
        return $this->taskModificationModel->update(
            [
                'id' => $data['task_id'],
                'title' => $newTitle
            ]
        );
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
        return $data['task']['column_id'] == $this->getParam('column_id');
    }
}