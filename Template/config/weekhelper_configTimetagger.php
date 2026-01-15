<div class="page-header">
    <h2><?= t('WeekHelper Timetagger configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigTimetagger', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>

    <!-- Timetagger main API config -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Timetagger API URL'), 'timetagger_url') ?>
            <?= $this->form->text('timetagger_url', ['timetagger_url' => $timetagger_url], [], []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Timetagger Authtoken'), 'timetagger_authtoken') ?>
            <?= $this->form->text('timetagger_authtoken', ['timetagger_authtoken' => $timetagger_authtoken], [], []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Timetagger Cookies'), 'timetagger_cookies') ?>
            <?= $this->form->text('timetagger_cookies', ['timetagger_cookies' => $timetagger_cookies], [], [
                'placeholder="e.g. \'security_cookie_a=abc; security_cookie_b=def\'"'
            ]) ?>
        </div>
        <p class="task-form-main-column weekhelper-config-weak-text">
            <?= t('Optional cookies can be set as a single HEADER string here, in case further security cookies or so has to be set.') ?>
        </p>

    </div>

    <br>
    <br>



    <!-- What the implementation should do -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Timetagger can overwrite spent times for "active week" tasks'), 'timetagger_overwrites_active_spent') ?>
            <?= $this->form->checkbox('timetagger_overwrites_active_spent', t('enabled'), 1, $timetagger_overwrites_active_spent) ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t('If enabled and if the task key "timetagger_tags" exist, internally the spent times for the tasks will be used from the actual Timetagger time tracking.') ?>
    </p>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
