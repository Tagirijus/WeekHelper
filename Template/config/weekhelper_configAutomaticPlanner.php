<div class="page-header">
    <h2><?= t('WeekHelper automatic planner configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigAutomaticPlanner', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Show sticky week plan'), 'automatic_planner_sticky_enabled') ?>
            <?= $this->form->checkbox('automatic_planner_sticky_enabled', t('enabled'), 1, $automatic_planner_sticky_enabled) ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t('Show a sticky div on the screen, which contains the automatic week planning.') ?>
    </p>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
