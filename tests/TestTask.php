<?php

namespace Kanboard\Plugin\WeekHelper\tests;


/**
 * This class can create simple tests tasks as if they (almost)
 * were original Kanboard task arrays.
 */
class TestTask
{
    private static int $nextId = 1;
    private static int $nextSubId = 1;

    /**
     * The parameter should be used in a named mannor, since
     * the order will change in the future, if I add new
     * possible task data elements. I will use it in an
     * alphabetically order.
     */
    public static function create(
        $column_name = '',
        $description = '',
        $project_id = -1,
        $project_max_hours_day = 0,
        $project_max_hours_fri = -1,
        $project_max_hours_mon = -1,
        $project_max_hours_sat = -1,
        $project_max_hours_sun = -1,
        $project_max_hours_thu = -1,
        $project_max_hours_tue = -1,
        $project_max_hours_wed = -1,
        $project_type = '',
        $score = 0,
        $time_estimated = 0.0,
        $time_remaining = 0.0,
        $time_spent = 0.0,
        $title = '',
        $user_id = -1,
    )
    {
        $id = self::$nextId++;
        return [
            'column_name' => $column_name,
            'description' => $description,
            'id' => $id,
            'owner_id' => $user_id,
            'project_id' => $project_id,
            'project_max_hours_day' => $project_max_hours_day,
            'project_max_hours_fri' => $project_max_hours_fri,
            'project_max_hours_mon' => $project_max_hours_mon,
            'project_max_hours_sat' => $project_max_hours_sat,
            'project_max_hours_sun' => $project_max_hours_sun,
            'project_max_hours_thu' => $project_max_hours_thu,
            'project_max_hours_tue' => $project_max_hours_tue,
            'project_max_hours_wed' => $project_max_hours_wed,
            'project_type' => $project_type,
            'score' => $score,
            'time_estimated' => $time_estimated,
            'time_remaining' => $time_remaining,
            'time_spent' => $time_spent,
            'title' => $title,
        ];
    }

    /**
     * Create a subtask from scratch. As for the task ( create() )
     * the parameters should be handed by naming them.
     */
    public static function createSub(
        $status = 0,
        $task_id = -1,
        $time_estimated = 0.0,
        $time_remaining = 0.0,
        $time_spent = 0.0,
        $title = '',
    )
    {
        $id = self::$nextSubId++;
        return [
            'id' => $id,
            'status' => $status,
            'task_id' => $task_id,
            'time_estimated' => $time_estimated,
            'time_remaining' => $time_remaining,
            'time_spent' => $time_spent,
            'title' => $title,
        ];
    }
}
