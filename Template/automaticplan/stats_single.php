<?php
    use Kanboard\Plugin\WeekHelper\Helper\TimeHelper;
?>

<div class="plan-stats-big">
    Planned: <?= TimeHelper::minutesToReadable($tasksplan->getGlobalTimesForWeek()['planned'], 'h') ?>
    &nbsp;&dash;&nbsp;
    Spent: <?= TimeHelper::minutesToReadable($tasksplan->getGlobalTimesForWeek()['spent'], 'h') ?>
    &nbsp;&dash;&nbsp;
    Overflow: <?= TimeHelper::minutesToReadable($tasksplan->getGlobalTimesForOverflow()['planned'], 'h') ?>
    &nbsp;&dash;&nbsp;
    Free: <?= TimeHelper::minutesToReadable($tasksplan->getAvailableSlotTime('all'), 'h') ?>
</div>

<div class="plan-stats-small">
    P: <?= TimeHelper::minutesToReadable($tasksplan->getGlobalTimesForWeek()['planned'], 'h') ?>
    &nbsp;&dash;&nbsp;
    S: <?= TimeHelper::minutesToReadable($tasksplan->getGlobalTimesForWeek()['spent'], 'h') ?>
    &nbsp;&dash;&nbsp;
    O: <?= TimeHelper::minutesToReadable($tasksplan->getGlobalTimesForOverflow()['planned'], 'h') ?>
    &nbsp;&dash;&nbsp;
    F: <?= TimeHelper::minutesToReadable($tasksplan->getAvailableSlotTime('all'), 'h') ?>
</div>