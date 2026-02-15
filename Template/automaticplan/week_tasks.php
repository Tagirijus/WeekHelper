<?php
    use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
?>

<?php if (!empty($tasks)): ?>
    <div class="plan-day">
        <?= strtoupper($day) ?>
        <span class="plan-day-planned">
            (<?= TimeHelper::minutesToReadable($daytimes['planned'], ' h') ?>)
        </span>
    </div>
    <ul class="plan-list">
        <?php $last_time = -1; ?>
        <?php foreach ($tasks as $task): ?>
            <?php if ($last_time != -1 && $last_time != $task['start']): ?>
                <li>
                    <div class="plan-entry-line">
                        <div class="plan-entry-time"></div>
                        <div class="task-board plan-entry-task"></div>
                    </div>
                </li>
            <?php endif ?>
            <li>
                <?= $this->render('WeekHelper:automaticplan/single_task', [
                    'task' => $task,
                    'day' => $day
                ]) ?>
            </li>
            <?php $last_time = $task['end']; ?>
        <?php endforeach ?>
    </ul>
    <br>
<?php endif ?>
