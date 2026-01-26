<?php

    $this->helper->hoursViewHelper->initTasks();
    $times = $this->hoursViewHelper->getTimes();

    function percentFromTotal($this_times, $total_times)
    {
        if ($total_times != 0) {
            $percent = round($this_times / $total_times * 100);
        } else {
            $percent = 0;
        }
        return $percent . '%';
    }

?>

<div class="tooltip-large">
    <h1>
        <?= $label ?>
    </h1>
    <br>
    <table class="table-small">
        <tr>
            <th>
                Name
            </th>
            <th>
                <?= t('Estimated') ?>
            </th>
            <th>
                <?= t('Spent') ?>
            </th>
            <th>
                <?= t('Remaining') ?>
            </th>
        </tr>

        <?php foreach ($times->getProjectIdsByLevel($level) as $project_id): ?>

            <?php $project = $this->hoursViewHelper->getProject($project_id); ?>

            <?php if (!$times->hasTimesByProjectLevel($project_id, $level)): continue ?>
            <?php endif ?>

            <tr>
                <td>
                    <a href="/board/<?= $project_id ?>"><?= $project['name'] ?></a>
                </td>
                <td>
                    <span class="thv-estimated-color">
                        <?= $times->getEstimatedByProjectLevel($project_id, $level, true) ?>h
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($times->getEstimatedByProjectLevel($project_id, $level), $times->getEstimatedByLevel($level)) ?>
                    </span>
                </td>
                <td>
                    <span class="thv-spent-color">
                        <?= $times->getSpentByProjectLevel($project_id, $level, true) ?>h
                        <?php if ($times->getOvertimeByProjectLevel($project_id, $level) != 0.0): ?>
                            <i class="thv-font-weak">
                                (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentByProjectLevel($project_id, $level), $times->getOvertimeByProjectLevel($project_id, $level)); ?>)
                            </i>
                        <?php endif ?>
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($times->getSpentByProjectLevel($project_id, $level), $times->getSpentByLevel($level)) ?>
                    </span>
                </td>
                <td>
                    <span class="thv-remaining-color">
                        <?= $times->getRemainingByProjectLevel($project_id, $level, true) ?>h
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($times->getRemainingByProjectLevel($project_id, $level), $times->getRemainingByLevel($level)) ?>
                    </span>
                </td>
            </tr>
        <?php endforeach ?>
        <tr class="thv-font-weak">
            <td>
                Total
            </td>
            <td>
                <span class="thv-estimated-color">
                    <?= $times->getEstimatedByLevel($level, true) ?>h
                </span>
            </td>
            <td>
                <span class="thv-spent-color">
                    <?= $times->getSpentByLevel($level, true) ?>h
                    <?php if ($times->getOvertimeByLevel($level) != 0.0): ?>
                        <i class="thv-font-weak">
                            (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentByLevel($level), $times->getOvertimeByLevel($level)); ?>)
                        </i>
                    <?php endif ?>
                </span>
            </td>
            <td>
                <span class="thv-remaining-color">
                    <?= $times->getRemainingByLevel($level, true) ?>h
                </span>
            </td>
        </tr>
    </table>
</div>
