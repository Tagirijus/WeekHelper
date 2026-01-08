<?php

namespace Kanboard\Plugin\WeekHelper\tests;


/**
 * This class can create simple tests tasks without the keys
 * of original Kanbaord tasks, I would not need inside the
 * tests.
 */
class TestTask
{
    private static int $nextId = 1;

    public static function create(
        $title,
        $project_id,
        $project_type,
        $project_max_hours_day,
        $time_remaining,
        $time_spent,
        $project_max_hours_mon = -1,
        $project_max_hours_tue = -1,
        $project_max_hours_wed = -1,
        $project_max_hours_thu = -1,
        $project_max_hours_fri = -1,
        $project_max_hours_sat = -1,
        $project_max_hours_sun = -1,
    )
    {
        $id = self::$nextId++;
        return [
            'id' => $id,
            'title' => $title,
            'project_id' => $project_id,
            'project_type' => $project_type,
            'project_max_hours_day' => $project_max_hours_day,
            'time_remaining' => $time_remaining,
            'time_spent' => $time_spent,
            'project_max_hours_mon' => $project_max_hours_mon,
            'project_max_hours_tue' => $project_max_hours_tue,
            'project_max_hours_wed' => $project_max_hours_wed,
            'project_max_hours_thu' => $project_max_hours_thu,
            'project_max_hours_fri' => $project_max_hours_fri,
            'project_max_hours_sat' => $project_max_hours_sat,
            'project_max_hours_sun' => $project_max_hours_sun,
        ];
    }
}
