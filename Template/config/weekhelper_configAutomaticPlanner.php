<div class="page-header">
    <h2><?= t('WeekHelper automatic planner configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigAutomaticPlanner', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>

    <!-- Enable sticky planner -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Show sticky week plan'), 'automatic_planner_sticky_enabled') ?>
            <?= $this->form->checkbox('automatic_planner_sticky_enabled', t('enabled'), 1, $automatic_planner_sticky_enabled) ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t('Show a sticky div on the screen, which contains the automatic week planning.') ?>
    </p>

    <br>
    <br>

    <span>Active / planned week</span>


    <!-- which levels are planned vs. active week? -->

    <p class="weekhelper-config-weak-text">
        <?= t('Technically the automatic planner will plan the task form the "active week" into the actual running week and the task from the "planned week" onto the next week. Tell the system which level stands for which of those weeks.') ?>
    </p>

    <div class="task-form-container">
        <div class="task-form-main-column">
            <?= $this->form->label(t('Level to use for the active week.'), 'level_active_week') ?>
            <?= $this->form->text('level_active_week', ['level_active_week' => $level_active_week], [], [
                'autofocus',
                'tabindex="9"',
                'placeholder="e.g. \'level_1\' or \'all\'"'
            ]) ?>
        </div>
    </div>

    <div class="task-form-container">
        <div class="task-form-main-column">
            <?= $this->form->label(t('Level to use for the planned week.'), 'level_planned_week') ?>
            <?= $this->form->text('level_planned_week', ['level_planned_week' => $level_planned_week], [], [
                'autofocus',
                'tabindex="9"',
                'placeholder="e.g. \'level_1\' or \'all\'"'
            ]) ?>
        </div>
    </div>


    <!-- time slots help text -->
    <?php $slots_help = 'A slot should contain 24h format starting and end times (e.g. "6:00-9:00"). It can also contain a single project type after that times, separated with a single whitespace. Final example: "6:00-9:00 office". Multiple lines for time slots are possible.' ?>


    <!-- Monday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Monday time slots'), 'monday_slots') ?>
            <?= $this->form->textarea('monday_slots', ['monday_slots' => $monday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Tuesday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Tuesday time slots'), 'tuesday_slots') ?>
            <?= $this->form->textarea('tuesday_slots', ['tuesday_slots' => $tuesday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Wednesday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Wednesday time slots'), 'wednesday_slots') ?>
            <?= $this->form->textarea('wednesday_slots', ['wednesday_slots' => $wednesday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Thursday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Thursday time slots'), 'thursday_slots') ?>
            <?= $this->form->textarea('thursday_slots', ['thursday_slots' => $thursday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Friday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Friday time slots'), 'friday_slots') ?>
            <?= $this->form->textarea('friday_slots', ['friday_slots' => $friday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Saturday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Saturday time slots'), 'saturday_slots') ?>
            <?= $this->form->textarea('saturday_slots', ['saturday_slots' => $saturday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Sunday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Sunday time slots'), 'sunday_slots') ?>
            <?= $this->form->textarea('sunday_slots', ['sunday_slots' => $sunday_slots], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
