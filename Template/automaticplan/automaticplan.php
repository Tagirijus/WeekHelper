<?php
    $array_plan = $planner->getAutomaticPlanAsArray(true);
    $active = $array_plan['active'];
    $active_tasksplan = $planner->getTasksPlan('active');
    $planned = $array_plan['planned'];
    $planned_tasksplan = $planner->getTasksPlan('planned');

    // selected tabs fetched from cookies
    if (($_COOKIE['plan-tab-selected'] ?? '') == 'active') {
        $btn_active_class = 'btn-blue';
        $container_active_class = '';
    } else {
        $btn_active_class = '';
        $container_active_class = 'plan-hidden';
    }
    if (($_COOKIE['plan-tab-selected'] ?? '') == 'planned') {
        $btn_planned_class = 'btn-blue';
        $container_planned_class = '';
    } else {
        $btn_planned_class = '';
        $container_planned_class = 'plan-hidden';
    }
    if (($_COOKIE['plan-tab-selected'] ?? '') == 'config') {
        $btn_config_class = 'btn-blue';
        $container_config_class = '';
    } else {
        $btn_config_class = '';
        $container_config_class = 'plan-hidden';
    }
?>

<div class="plan-wrapper">

    <button class="plan-btn btn <?= $btn_active_class ?>" data-plan-selected-btn="active">Active</button>
    <button class="plan-btn btn <?= $btn_planned_class ?>" data-plan-selected-btn="planned">Planned</button>
    <button class="plan-btn btn <?= $btn_config_class ?>" data-plan-selected-btn="config">Config</button>

    <div class="plan-stats">
        <div class="plan-container <?= $container_active_class ?>" data-plan-tab="active">
            <?= $this->render('WeekHelper:automaticplan/stats_single', [
                'tasksplan' => $planner->getTasksPlan('active')
            ]) ?>
        </div>
        <div class="plan-container <?= $container_planned_class ?>" data-plan-tab="planned">
            <?= $this->render('WeekHelper:automaticplan/stats_single', [
                'tasksplan' => $planner->getTasksPlan('planned')
            ]) ?>
        </div>
    </div>

    <div class="plan-container <?= $container_active_class ?>" data-plan-tab="active">
        <?php foreach ($active as $day => $tasks): ?>
            <?= $this->render('WeekHelper:automaticplan/week_tasks', [
                'tasks' => $tasks,
                'day' => $day,
                'daytimes' => $active_tasksplan->getGlobalTimesForDay($day),
            ]) ?>
        <?php endforeach ?>
    </div>

    <div class="plan-container <?= $container_planned_class ?>" data-plan-tab="planned">
        <?php foreach ($planned as $day => $tasks): ?>
            <?= $this->render('WeekHelper:automaticplan/week_tasks', [
                'tasks' => $tasks,
                'day' => $day,
                'daytimes' => $planned_tasksplan->getGlobalTimesForDay($day),
            ]) ?>
        <?php endforeach ?>
    </div>

    <div class="plan-container <?= $container_config_class ?>" data-plan-tab="config">
        <div class="plan-day">to be made ...</div>
    </div>

</div>
