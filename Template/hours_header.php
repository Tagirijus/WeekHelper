<?php $times = $this->hoursViewHelper->getTimes(); ?>

<div class="thv-box-wrapper-board thv-font-small">


    <?php foreach ($captions as $level => $caption): ?>


        <?php if ($caption != ''): ?>

            <div class="thv-box-item">

                <div class="thv-weak-color tvh-box-single-item-20">
                    <?php if (!$projectSite): ?>
                        <span class="tooltip" data-href="/?controller=WeekHelperController&amp;plugin=WeekHelper&amp;action=getTooltipDashboardTimes&amp;level=<?= $level ?>"><i class="fa fa-bars"></i></span>
                    <?php endif ?>
                    <?= $caption ?>:
                </div>

                <div class="tvh-box-single-item-20">
                    <span class="thv-title-color">
                        <?= t('Estimated'); ?>:
                    </span>
                    <span class="thv-estimated-color">
                        <?= $times->getEstimatedPerLevel($level, true); ?>h
                    </span>
                </div>

                <div class="tvh-box-single-item-40">
                    <span class="thv-title-color">
                        <?= t('Spent'); ?>:
                    </span>
                    <span class="thv-spent-color">
                        <?= $times->getSpentPerLevel($level, true); ?>h
                        <?php if ($times->getOvertimePerLevel($level) != 0.0): ?>
                            <i class="thv-font-weak">
                                (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentPerLevel($level), $times->getOvertimePerLevel($level)); ?>)
                            </i>
                        <?php endif ?>
                    </span>
                </div>

                <div class="tvh-box-single-item-20">
                    <span class="thv-title-color">
                        <?= t('Remaining'); ?>:
                    </span>
                    <span class="thv-remaining-color">
                        <?= $times->getRemainingPerLevel($level, true); ?>h
                    </span>
                </div>

            </div>

        <?php endif ?>


    <?php endforeach ?>


</div>
