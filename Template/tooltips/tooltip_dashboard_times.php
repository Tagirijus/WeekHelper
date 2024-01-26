<?php

    $timesTotal = $this->helper->hoursViewHelper->getTimesByUserId($this->user->getId());

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
        <?php foreach ($times as $projectId => $project): ?>
            <?php $time = $project['times']; ?>
            <?php if (!$time[$level]['_has_times']): continue ?>
            <?php endif ?>
            <tr>
                <td>
                    <a href="/board/<?= $projectId ?>"><?= $project['name'] ?></a>
                </td>
                <td>
                    <span class="thv-estimated-color">
                        <?= $this->hoursViewHelper->floatToHHMM($time[$level]['_total']['estimated']) ?>h
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($time[$level]['_total']['estimated'], $timesTotal[$level]['_total']['estimated']) ?>
                    </span>
                </td>
                <td>
                    <span class="thv-spent-color">
                        <?= $this->hoursViewHelper->floatToHHMM($time[$level]['_total']['spent']) ?>h
                        <?php if ($time[$level]['_total']['overtime'] != 0.0): ?>
                            <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($time[$level]['_total']['spent'] - $time[$level]['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($time[$level]['_total']['overtime']); ?>)</i>
                        <?php endif ?>
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($time[$level]['_total']['spent'], $timesTotal[$level]['_total']['spent']) ?>
                    </span>
                </td>
                <td>
                    <span class="thv-remaining-color">
                        <?= $this->hoursViewHelper->floatToHHMM($time[$level]['_total']['remaining']) ?>h
                    </span>
                    <br>
                    <span class="thv-font-small">
                        <?= percentFromTotal($time[$level]['_total']['remaining'], $timesTotal[$level]['_total']['remaining']) ?>
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
                    <?= $this->hoursViewHelper->floatToHHMM($timesTotal[$level]['_total']['estimated']) ?>h
                </span>
            </td>
            <td>
                <span class="thv-spent-color">
                    <?= $this->hoursViewHelper->floatToHHMM($timesTotal[$level]['_total']['spent']) ?>h
                    <?php if ($timesTotal[$level]['_total']['overtime'] != 0.0): ?>
                        <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($timesTotal[$level]['_total']['spent'] - $timesTotal[$level]['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($timesTotal[$level]['_total']['overtime']); ?>)</i>
                    <?php endif ?>
                </span>
            </td>
            <td>
                <span class="thv-remaining-color">
                    <?= $this->hoursViewHelper->floatToHHMM($timesTotal[$level]['_total']['remaining']) ?>h
                </span>
            </td>
        </tr>
    </table>
</div>
