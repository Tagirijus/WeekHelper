<?php
    $array_plan = $planner->getAutomaticPlanAsArray(true);
    $active = $array_plan['active'];
    $planned = $array_plan['planned'];
?>

<?php foreach ($active as $day => $tasks): ?>
    <?php if (!empty($tasks)): ?>
        <strong><?= strtoupper($day) ?></strong>
        <ul class="plan-list">
            <?php foreach ($tasks as $task): ?>
                <li>
                    <?= $this->render('WeekHelper:automaticplan/single_task', [
                        'task' => $task,
                        'day' => $day
                    ]) ?>
                </li>
            <?php endforeach ?>
        </ul>
        <br>
    <?php endif ?>
<?php endforeach ?>
