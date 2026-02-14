<?php

// for my other plugin (actually a plugin I did only modify!)
// I need more than just the project tasks; so in case this
// plugin calls this template, I will simply initialize
// all tasks.
if ($caller_controller == 'Bigboard') {
    $this->hoursViewHelper->initTasks();
} else {
    $this->hoursViewHelper->initTasks('project', $column['project_id']);
}
$times = $this->hoursViewHelper->getTimes();
$column_name = $column['title'];
$swimlane_name = $swimlane['name'];
$hover_text = t('Estimated') . ': ' . $times->getEstimatedBySwimlaneColumn($swimlane_name, $column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Spent') . ': ' . $times->getSpentBySwimlaneColumn($swimlane_name, $column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Remaining') . ': ' . $times->getRemainingBySwimlaneColumn($swimlane_name, $column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Overtime') . ': ' . $times->getOvertimeBySwimlaneColumn($swimlane_name, $column_name, true) . 'h';

?>

<div class="thv-column-wrapper thv-font-small" title="<?= $hover_text ?>">
    <span class="ui-helper-hidden-accessible"><?= $hover_text ?></span>
    <span class="thv-font-smallB">
        <span class="thv-spent-color">
            <?= $times->getSpentBySwimlaneColumn($swimlane_name, $column_name, true); ?>
        </span>/<span class="thv-estimated-color">
            <?= $times->getEstimatedBySwimlaneColumn($swimlane_name, $column_name, true); ?>
        </span><br>
    </span>
    <span class="thv-remaining-color thv-font-big">
        <?= $times->getRemainingBySwimlaneColumn($swimlane_name, $column_name, true); ?>
    </span>
</div>
