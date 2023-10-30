<!--
    When this template gets "$alt_colors == 1" as a variable,
    it will change the CSS classes accordingl to show white text
    and borders instead of black.
-->

<!-- Define the classes to use here -->

<?php

    if (!isset($alt_colors)) {
        $alt_colors = 0;
    }

    if ($alt_colors == 1) {

        $task_icon_class_days = 'wh-remaining-days';
        // $task_icon_class_days .= ' wh-task-icon-normal';

        $task_icon_class_weeks = 'wh-remaining-weeks';
        // $task_icon_class_weeks .= ' wh-task-icon-normal';

    } else {

        $task_icon_class_days = 'wh-remaining-days';
        // $task_icon_class_days .= ' wh-task-icon-normal';

        $task_icon_class_weeks = 'wh-remaining-weeks';
        // $task_icon_class_weeks .= ' wh-task-icon-normal';

    }

?>

<div class="wh-remaining-box">
    <div
        title="<?= t('Remaining days')?>"
        class="<?= $task_icon_class_days ?>"
    >
        <span class="ui-helper-hidden-accessible"><?= t('Remaining days') ?> </span>
        <?= $this->weekHelperHelper->getRemainingDaysFromTimestamp($task['date_due']) ?>D
    </div>
    <div
        title="<?= t('Remaining weeks')?>"
        class="<?= $task_icon_class_weeks ?>"
    >
        <span class="ui-helper-hidden-accessible"><?= t('Remaining weeks') ?> </span>
        <?= $this->weekHelperHelper->getRemainingWeeksFromTimestamp($task['date_due']) ?>W
    </div>
</div>
