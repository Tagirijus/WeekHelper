<?php

$times = $this->hoursViewHelper->getTimes();
$column_name = $column['title'];
$hover_text = t('Estimated') . ': ' . $times->getEstimatedPerColumn($column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Spent') . ': ' . $times->getSpentPerColumn($column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Remaining') . ': ' . $times->getRemainingPerColumn($column_name, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Overtime') . ': ' . $times->getOvertimePerColumn($column_name, true) . 'h';

?>


<div class="thv-column-wrapper thv-font-small" title="<?= $hover_text ?>">
    <span class="ui-helper-hidden-accessible"><?= $hover_text ?></span>
    <span class="thv-font-smallB">
        <span class="thv-spent-color">
            <?= $times->getSpentPerColumn($column_name, true); ?>
        </span>/<span class="thv-estimated-color">
            <?= $times->getEstimatedPerColumn($column_name, true); ?>
        </span><br>
    </span>
    <span class="thv-remaining-color thv-font-big">
        <?= $times->getRemainingPerColumn($column_name, true); ?>
    </span>
</div>
