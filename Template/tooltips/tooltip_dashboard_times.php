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

            <?php if (!$times->hasTimesByProject($project_id)): continue ?>
            <?php endif ?>

            <tr>
                <td>
                    <a href="/board/<?= $project_id ?>"><?= $project['name'] ?></a>
                </td>
                <td>
                    <span class="thv-estimated-color">
                        <?= $times->getEstimatedByProject($project_id, true) ?>h
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($times->getEstimatedByProject($project_id), $times->getEstimatedTotal()) ?>
                    </span>
                </td>
                <td>
                    <span class="thv-spent-color">
                        <?= $times->getSpentByProject($project_id, true) ?>h
                        <?php if ($times->getOvertimeByProject($project_id) != 0.0): ?>
                            <i class="thv-font-weak">
                                (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentByProject($project_id), $times->getOvertimeByProject($project_id)); ?>)
                            </i>
                        <?php endif ?>
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($times->getSpentByProject($project_id), $times->getSpentTotal()) ?>
                    </span>
                </td>
                <td>
                    <span class="thv-remaining-color">
                        <?= $times->getRemainingByProject($project_id, true) ?>h
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($times->getRemainingByProject($project_id), $times->getRemainingTotal()) ?>
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
                    <?= $times->getEstimatedTotal(true) ?>h
                </span>
            </td>
            <td>
                <span class="thv-spent-color">
                    <?= $times->getSpentTotal(true) ?>h
                    <?php if ($times->getOvertimeTotal() != 0.0): ?>
                        <i class="thv-font-weak">
                            (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentTotal(), $times->getOvertimeTotal()); ?>)
                        </i>
                    <?php endif ?>
                </span>
            </td>
            <td>
                <span class="thv-remaining-color">
                    <?= $times->getRemainingTotal(true) ?>h
                </span>
            </td>
        </tr>
    </table>
</div>
