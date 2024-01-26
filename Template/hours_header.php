<div class="thv-box-wrapper-board thv-font-small">


    <!-- LEVEL 1 -->
    <?php if ($captions['level_1'] != ''): ?>

        <div class="thv-box-item">

            <div class="thv-weak-color tvh-box-single-item-20">
                <?php if (!$projectSite): ?>
                    <span class="tooltip" data-href="/?controller=WeekHelperController&amp;plugin=WeekHelper&amp;action=getTooltipDashboardTimes&amp;level=level_1"><i class="fa fa-bars"></i></span>
                <?php endif ?>
                <?= $captions['level_1'] ?>:
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Estimated'); ?>:
                </span>
                <span class="thv-estimated-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_1']['_total']['estimated']); ?>h
                </span>
            </div>

            <div class="tvh-box-single-item-40">
                <span class="thv-title-color">
                    <?= t('Spent'); ?>:
                </span>
                <span class="thv-spent-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_1']['_total']['spent']); ?>h
                    <?php if ($times['level_1']['_total']['overtime'] != 0.0): ?>
                        <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($times['level_1']['_total']['spent'] - $times['level_1']['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($times['level_1']['_total']['overtime']); ?>)</i>
                    <?php endif ?>
                </span>
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Remaining'); ?>:
                </span>
                <span class="thv-remaining-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_1']['_total']['remaining']); ?>h
                </span>
            </div>

        </div>

    <?php endif ?>


    <!-- LEVEL 2 -->
    <?php if ($captions['level_2'] != ''): ?>

        <div class="thv-box-item">

            <div class="thv-weak-color tvh-box-single-item-20">
                <?php if (!$projectSite): ?>
                    <span class="tooltip" data-href="/?controller=WeekHelperController&amp;plugin=WeekHelper&amp;action=getTooltipDashboardTimes&amp;level=level_2"><i class="fa fa-bars"></i></span>
                <?php endif ?>
                <?= $captions['level_2'] ?>:
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Estimated'); ?>:
                </span>
                <span class="thv-estimated-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_2']['_total']['estimated']); ?>h
                </span>
            </div>

            <div class="tvh-box-single-item-40">
                <span class="thv-title-color">
                    <?= t('Spent'); ?>:
                </span>
                <span class="thv-spent-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_2']['_total']['spent']); ?>h
                    <?php if ($times['level_2']['_total']['overtime'] != 0.0): ?>
                        <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($times['level_2']['_total']['spent'] - $times['level_2']['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($times['level_2']['_total']['overtime']); ?>)</i>
                    <?php endif ?>
                </span>
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Remaining'); ?>:
                </span>
                <span class="thv-remaining-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_2']['_total']['remaining']); ?>h
                </span>
            </div>

        </div>

    <?php endif ?>


    <!-- LEVEL 3 -->
    <?php if ($captions['level_3'] != ''): ?>

        <div class="thv-box-item">

            <div class="thv-weak-color tvh-box-single-item-20">
                <?php if (!$projectSite): ?>
                    <span class="tooltip" data-href="/?controller=WeekHelperController&amp;plugin=WeekHelper&amp;action=getTooltipDashboardTimes&amp;level=level_3"><i class="fa fa-bars"></i></span>
                <?php endif ?>
                <?= $captions['level_3'] ?>:
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Estimated'); ?>:
                </span>
                <span class="thv-estimated-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_3']['_total']['estimated']); ?>h
                </span>
            </div>

            <div class="tvh-box-single-item-40">
                <span class="thv-title-color">
                    <?= t('Spent'); ?>:
                </span>
                <span class="thv-spent-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_3']['_total']['spent']); ?>h
                    <?php if ($times['level_3']['_total']['overtime'] != 0.0): ?>
                        <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($times['level_3']['_total']['spent'] - $times['level_3']['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($times['level_3']['_total']['overtime']); ?>)</i>
                    <?php endif ?>
                </span>
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Remaining'); ?>:
                </span>
                <span class="thv-remaining-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_3']['_total']['remaining']); ?>h
                </span>
            </div>

        </div>

    <?php endif ?>


    <!-- LEVEL 4 -->
    <?php if ($captions['level_4'] != ''): ?>

        <div class="thv-box-item">

            <div class="thv-weak-color tvh-box-single-item-20">
                <?php if (!$projectSite): ?>
                    <span class="tooltip" data-href="/?controller=WeekHelperController&amp;plugin=WeekHelper&amp;action=getTooltipDashboardTimes&amp;level=level_4"><i class="fa fa-bars"></i></span>
                <?php endif ?>
                <?= $captions['level_4'] ?>:
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Estimated'); ?>:
                </span>
                <span class="thv-estimated-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_4']['_total']['estimated']); ?>h
                </span>
            </div>

            <div class="tvh-box-single-item-40">
                <span class="thv-title-color">
                    <?= t('Spent'); ?>:
                </span>
                <span class="thv-spent-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_4']['_total']['spent']); ?>h
                    <?php if ($times['level_4']['_total']['overtime'] != 0.0): ?>
                        <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($times['level_4']['_total']['spent'] - $times['level_4']['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($times['level_4']['_total']['overtime']); ?>)</i>
                    <?php endif ?>
                </span>
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Remaining'); ?>:
                </span>
                <span class="thv-remaining-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['level_4']['_total']['remaining']); ?>h
                </span>
            </div>

        </div>

    <?php endif ?>


    <!-- ALL -->
    <?php if ($captions['all'] != ''): ?>

        <div class="thv-box-item thv-box-all">

            <div class="thv-weak-color tvh-box-single-item-20">
                <?php if (!$projectSite): ?>
                    <span class="tooltip" data-href="/?controller=WeekHelperController&amp;plugin=WeekHelper&amp;action=getTooltipDashboardTimes&amp;level=all"><i class="fa fa-bars"></i></span>
                <?php endif ?>
                <?= $captions['all'] ?>:
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Estimated'); ?>:
                </span>
                <span class="thv-estimated-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['all']['_total']['estimated']); ?>h
                </span>
            </div>

            <div class="tvh-box-single-item-40">
                <span class="thv-title-color">
                    <?= t('Spent'); ?>:
                </span>
                <span class="thv-spent-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['all']['_total']['spent']); ?>h
                    <?php if ($times['all']['_total']['overtime'] != 0.0): ?>
                        <i class="thv-font-weak">(<?= $this->hoursViewHelper->floatToHHMM($times['all']['_total']['spent'] - $times['all']['_total']['overtime']); ?>h <?= $this->hoursViewHelper->getOvertimeForTaskAsString($times['all']['_total']['overtime']); ?>)</i>
                    <?php endif ?>
                </span>
            </div>

            <div class="tvh-box-single-item-20">
                <span class="thv-title-color">
                    <?= t('Remaining'); ?>:
                </span>
                <span class="thv-remaining-color">
                    <?= $this->hoursViewHelper->floatToHHMM($times['all']['_total']['remaining']); ?>h
                </span>
            </div>

        </div>

    <?php endif ?>


</div>
