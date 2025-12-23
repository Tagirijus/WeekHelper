<?php

namespace Kanboard\Plugin\WeekHelper\tests;


/**
 * This class can create simple tests tasks without the keys
 * of original Kanbaord tasks, I would not need inside the
 * tests.
 */
class TestTask
{
    private static int $nextId = 0;

    public static function create(
        $title,
        $project_id,
        $project_type,
        $project_max_hours_day,
        $time_remaining,
        $time_spent,
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
        ];
    }
}
