<?php
    $this->hoursViewHelper->initTasks('project', $project['id']);
    $times = $this->hoursViewHelper->getTimes();
    $hoursview_config = $this->hoursViewHelper->getConfig();
?>

<?php if (
    $hoursview_config['hide_0hours_projects_enabled'] == 0
    or $hoursview_config['hide_0hours_projects_enabled'] == 1 && $times->hasTimesByProjectHome($project['id'])
): ?>

    <div class="thv-board-column">
        &ndash;

        <span class="thv-estimated-color">
            <?= $times->getEstimatedByProjectHome($project['id'], true); ?>
        </span>
        <span></span>

        <span class="thv-spent-color">
            <?= $times->getSpentByProjectHome($project['id'], true); ?>
            <?php if ($times->getOvertimeByProjectHome($project['id']) != 0): ?>
                <i class="thv-font-small">
                    (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentByProjectHome($project['id']), $times->getOvertimeByProjectHome($project['id'])); ?>)
                </i>
            <?php endif ?>
        </span>
        <span></span>

        <span class="thv-remaining-color">
            <?= $times->getRemainingByProjectHome($project['id'], true); ?>
        </span>
        <span></span>


        <?php
            $output = $times->getPercentByProjectHome($project['id'], true);
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
