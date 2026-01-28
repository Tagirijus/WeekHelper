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
            <?= $this->form->label(t('Timetagger can overwrite times for tasks in these levels'), 'timetagger_overwrites_levels') ?>
            <?= $this->form->text('timetagger_overwrites_levels', ['timetagger_overwrites_levels' => $timetagger_overwrites_levels], [], [
                'placeholder="e.g. \'level_1,level_3\'"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Timetagger start for timerange'), 'timetagger_start_fetch') ?>
            <?= $this->form->text('timetagger_start_fetch', ['timetagger_start_fetch' => $timetagger_start_fetch], [], [
                'placeholder="e.g. \'monday this week\'"'
            ]) ?>
        </div>
        <p class="task-form-main-column weekhelper-config-weak-text">
            <?= t('This is a string which will be used in the strtotime() PHP method, which can describe with words a time point.') ?>
        </p>

    </div>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
