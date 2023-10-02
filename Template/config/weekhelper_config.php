<div class="page-header">
    <h2><?= t('WeekHelper configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfig', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
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



    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
