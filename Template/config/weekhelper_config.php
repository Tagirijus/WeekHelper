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



    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
