<?php
    $this->hoursViewHelper->initTasks('project', $project['id']);
    $times = $this->hoursViewHelper->getTimes();
    $hoursview_config = $this->hoursViewHelper->getConfig();
?>

<?php if (
    $hoursview_config['hide_0hours_projects_enabled'] == 0
    or $hoursview_config['hide_0hours_projects_enabled'] == 1 && $times->hasTimesByProject($project['id'])
): ?>

    <div class="thv-board-column">
        &ndash;

        <span class="thv-estimated-color">
            <?= $times->getEstimatedByProject($project['id'], true); ?>
        </span>
        <span></span>

        <span class="thv-spent-color">
            <?= $times->getSpentByProject($project['id'], true); ?>
            <?php if ($times->getOvertimeByProject($project['id']) != 0): ?>
                <i class="thv-font-small">
                    (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentByProject($project['id']), $times->getOvertimeByProject($project['id'])); ?>)
                </i>
            <?php endif ?>
        </span>
        <span></span>

        <span class="thv-remaining-color">
            <?= $times->getRemainingByProject($project['id'], true); ?>
        </span>
        <span></span>


        <?php
            $output = $times->getPercentByProject($project['id'], true);
        ?>
        <?php if ($output == '100%'): ?>
        <span class="thv-100-percent">
        <?php else: ?>
        <span>
        <?php endif ?>
            <?= $output; ?>
        </span>

    </div>

<?php endif ?>
