<?php if ($this->weekHelperHelper->showRemainingDays() || $this->weekHelperHelper->showRemainingWeeks()): ?>

    <div class="wh-remaining-box">

        <?php if ($this->weekHelperHelper->showRemainingDays()): ?>
            <div
                title="<?= t('Remaining days') ?>"
                class="wh-remaining-days"
                style="<?= $this->weekHelperHelper->getCSSForRemainingDaysTimestamp($task['date_due']) ?>"
            >
                <span class="ui-helper-hidden-accessible"><?= t('Remaining days') ?> </span>
                <?= $this->weekHelperHelper->getRemainingDaysFromTimestamp($task['date_due']) ?>D
            </div>
        <?php endif ?>

        <?php if ($this->weekHelperHelper->showRemainingWeeks()): ?>
            <div
                title="<?= t('Remaining weeks')?>"
                class="wh-remaining-weeks"
                style="<?= $this->weekHelperHelper->getCSSForRemainingWeeksTimestamp($task['date_due']) ?>"
            >
                <span class="ui-helper-hidden-accessible"><?= t('Remaining weeks') ?> </span>
                <?= $this->weekHelperHelper->getRemainingWeeksFromTimestamp($task['date_due']) ?>W
            </div>
        <?php endif ?>

    </div>

<?php endif ?>
