<?php

$times = $this->hoursViewHelper->getTimes();
$hover_text = t('Estimated') . ': ' . $times->getEstimatedPerColumn($column, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Spent') . ': ' . $times->getSpentPerColumn($column, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Remaining') . ': ' . $times->getRemainingPerColumn($column, true) . 'h';
$hover_text .= "\n";
$hover_text .= t('Overtime') . ': ' . $times->getOvertimePerColumn($column, true) . 'h';

?>


<div class="thv-column-wrapper thv-font-small" title="<?= $hover_text ?>">
    <span class="ui-helper-hidden-accessible"><?= $hover_text ?></span>
    <span class="thv-font-smallB">
        <span class="thv-spent-color">
            <?= $times->getSpentPerColumn($column, true); ?>
        </span>/<span class="thv-estimated-color">
            <?= $times->getEstimatedPerColumn($column, true); ?>
        </span><br>
    </span>
    <span class="thv-remaining-color thv-font-big">
        <?= $times->getRemainingPerColumn($column, true); ?>
    </span>
</div>
