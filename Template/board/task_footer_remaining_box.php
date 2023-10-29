<div class="task-icon-age">
    <span
        title="<?= t('Remaining days')?>"
        class="
            <?php if ($white_border): ?>
                wh-task-icon-border-white
            <?php else: ?>
                wh-task-icon-border-black
            <?php endif ?>
            wh-task-icon-remaining-days
            wh-task-icon-normal
        "
    >
        <span class="ui-helper-hidden-accessible"><?= t('Remaining days') ?> </span>
        <?= $this->weekHelperHelper->getRemainingDaysFromTimestamp($task['date_due']) ?>D
    </span>
    <span
        title="<?= t('Remaining weeks')?>"
        class="
            <?php if ($white_border): ?>
                wh-task-icon-border-white
            <?php else: ?>
                wh-task-icon-border-black
            <?php endif ?>
            wh-task-icon-remaining-weeks
            wh-task-icon-soon
        "
    >
        <span class="ui-helper-hidden-accessible"><?= t('Remaining weeks') ?> </span>
        <?= $this->weekHelperHelper->getRemainingWeeksFromTimestamp($task['date_due']) ?>W
    </span>
</div>
