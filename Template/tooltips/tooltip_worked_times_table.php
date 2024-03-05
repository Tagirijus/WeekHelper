<div class="tooltip-large">
    <table class="table-small">
        <tr>
            <th>
                <span style="font-size:1.2em;"><?= $table_name ?></span>
            </th>
            <th>
                <?= t('Estimated') ?>
            </th>
            <th>
                <?= t('Spent') ?>
            </th>
            <th>
                <?= t('Overtime') ?>
            </th>
            <th>
                <?= t('Remaining') ?>
            </th>
        </tr>
        <?php foreach ($taskTimes as $name => $times): ?>
            <tr>
                <td>
                    <?= $name ?>
                </td>
                <td>
                    <span class="thv-estimated-color">
                        <?= $this->hoursViewHelper->floatToHHMM($times['time_estimated']) ?>h
                    </span>
                </td>
                <td>
                    <span class="thv-spent-color">
                        <?= $this->hoursViewHelper->floatToHHMM($times['time_spent']) ?>h
                    </span>
                </td>
                <td>
                    <span class="thv-spent-color">
                        <?= $this->hoursViewHelper->floatToHHMM($times['time_overtime']) ?>h
                    </span>
                </td>
                <td>
                    <span class="thv-remaining-color">
                        <?= $this->hoursViewHelper->floatToHHMM($times['time_remaining']) ?>h
                    </span>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
</div>
