<div class="page-header">
    <h2><?= t('WeekHelper remaining box configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigRemainingBox', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Show remaining box for days'), 'remaining_days_enabled') ?>
            <?= $this->form->checkbox('remaining_days_enabled', t('enabled'), 1, $remaining_days_enabled) ?>
        </div>

    </div>
    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Remaining levels for days'), 'remaining_lvl_days') ?>
            <?= $this->form->textarea('remaining_lvl_days', ['remaining_lvl_days' => $remaining_lvl_days], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t('Every line has to be in the format "difference:CSS", e.g.: "3:rgba(100,0,255,.5)"') ?>
    </p>

    <br>
    <br>


    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Show remaining box for weeks'), 'remaining_weeks_enabled') ?>
            <?= $this->form->checkbox('remaining_weeks_enabled', t('enabled'), 1, $remaining_weeks_enabled) ?>
        </div>

    </div>
    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Remaining levels for weeks'), 'remaining_lvl_weeks') ?>
            <?= $this->form->textarea('remaining_lvl_weeks', ['remaining_lvl_weeks' => $remaining_lvl_weeks], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t('Every line has to be in the format "difference:CSS", e.g.: "3:rgba(100,0,255,.5)"') ?>
    </p>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
