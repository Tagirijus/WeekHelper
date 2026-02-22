<?php
    use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
?>

<div class="plan-entry-line">
    <div class="plan-entry-time">
        <?php if ($day != 'overflow'): ?>
            <?= TimeHelper::minutesToReadable($task['start']) ?> - <?= TimeHelper::minutesToReadable($task['end']) ?>
        <?php endif ?>
    </div>

    <div class="task-board plan-entry-task color-<?= $task['task']['color_id'] ?? '' ?>">
        <?php if (isset($task['task']['is_running'])): ?>
            🔴
        <?php endif ?>
        <?php if (isset($task['task']['id'])): ?>
            <a href="/board/<?= $task['task']['project_id'] ?>" class="plan-hover">
                [<?= $task['task']['project_alias'] ?>]
            </a>
            <a href="/task/<?= $task['task']['id'] ?>" class="plan-hover"><?= $task['task']['title'] ?></a>
            <a href="/task/<?= $task['task']['id'] ?>/edit" class="js-modal-large plan-ml">
                <i class="fa fa-edit fa-fw js-modal-large plan-icon"></i>
            </a>
            <span class="plan-entry-smaller-prio">
                <?= $this->task->renderPriority($task['task']['priority']) ?>
            </span>
            <div class="plan-task-length">
                <?= TimeHelper::minutesToReadable($task['length'], ' h') ?>
            </div>
        <?php else: ?>
            <?= $task['task']['title'] ?>
            (<?= TimeHelper::minutesToReadable($task['length'], ' h') ?>)
        <?php endif ?>
    </div>
</div>
