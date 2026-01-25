<?php

$this->hoursViewHelper->initTasks('project', $column['project_id']);
$times = $this->hoursViewHelper->getTimes();
$column_name = $column['title'];
$swimlane_name = $swimlane['name'];
$hover_text = t('Estimated') . ': ' . $times->getEstimatedByColumn($swimlane_name . $column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Spent') . ': ' . $times->getSpentByColumn($swimlane_name . $column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Remaining') . ': ' . $times->getRemainingByColumn($swimlane_name . $column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Overtime') . ': ' . $times->getOvertimeByColumn($swimlane_name . $column_name, true) . 'h';

?>


<div class="thv-column-wrapper thv-font-small" title="<?= $hover_text ?>">
    <span class="ui-helper-hidden-accessible"><?= $hover_text ?></span>
    <span class="thv-font-smallB">
        <span class="thv-spent-color">
            <?= $times->getSpentByColumn($swimlane_name . $column_name, true); ?>
        </span>/<span class="thv-estimated-color">
            <?= $times->getEstimatedByColumn($swimlane_name . $column_name, true); ?>
        </span><br>
    </span>
    <span class="thv-remaining-color thv-font-big">
        <?= $times->getRemainingByColumn($swimlane_name . $column_name, true); ?>
    </span>
</div>
