<div class="page-header">
    <h2><?= t('WeekHelper weeks configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigWeeks', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Date in the header'), 'headerdate_enabled') ?>
            <?= $this->form->checkbox('headerdate_enabled', t('enabled'), 1, $headerdate_enabled, '', [
                'autofocus',
                'tabindex="1"'
            ]) ?>
        </div>

    </div>

    <br>
    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Week insert pattern [triggered on "w" in task title input]'), 'week_pattern') ?>
            <?= $this->form->text('week_pattern', ['week_pattern' => $week_pattern], [], [
                '',
                'tabindex="2"',
                'placeholder="' . t('e.g. \'Y{YEAR_SHORT}-W{WEEK}\'') . '"'
            ]) ?>
            <?= $this->form->label(t('Options are: {YEAR_SHORT}=two digit year, {YEAR}=four digit year, {WEEK}=week number'), '', ['class="weekhelper-small-config-text"']) ?>
        </div>

    </div>

    <br>
    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Sticky time box at the bottom'), 'time_box_enabled') ?>
            <?= $this->form->checkbox('time_box_enabled', t('enabled'), 1, $time_box_enabled, '', [
                'autofocus',
                'tabindex="3"'
            ]) ?>
        </div>

    </div>

    <br>
    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Week of due date on card'), 'due_date_week_card_enabled') ?>
            <?= $this->form->checkbox('due_date_week_card_enabled', t('enabled'), 1, $due_date_week_card_enabled, '', [
                'autofocus',
                'tabindex="4"'
            ]) ?>
        </div>

    </div>

    <br>
    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Show full started date on card'), 'full_start_date_enabled') ?>
            <?= $this->form->checkbox('full_start_date_enabled', t('enabled'), 1, $full_start_date_enabled, '', [
                'autofocus',
                'tabindex="5"'
            ]) ?>
        </div>

    </div>

    <br>
    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Week of due date in list'), 'due_date_week_list_enabled') ?>
            <?= $this->form->checkbox('due_date_week_list_enabled', t('enabled'), 1, $due_date_week_list_enabled, '', [
                'autofocus',
                'tabindex="6"'
            ]) ?>
        </div>

    </div>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
