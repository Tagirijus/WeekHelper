<div class="page-header">
    <h2><?= t('WeekHelper hours view configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigHoursView', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>


    <!-- LEVEL COLUMNS -->

    <p>
        <h3><?= t('Levels columns') ?></h3>
    </p>

    <p>
        <?= t('Each level can have comma seperated column names. This columns will be used for this levels time calculation. You can write swimlane in []-brackets to match these as well') ?>
    </p>
    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label('Level 1 ' . t('Columns'), 'level_1_columns') ?>
            <?= $this->form->text('level_1_columns', ['level_1_columns' => $level_1_columns], [], [
                'autofocus',
                'tabindex="1"',
                'placeholder="' . t('If empty, time will always be 0') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 2 ' . t('Columns'), 'level_2_columns') ?>
            <?= $this->form->text('level_2_columns', ['level_2_columns' => $level_2_columns], [], [
                'autofocus',
                'tabindex="2"',
                'placeholder="' . t('If empty, time will always be 0') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 3 ' . t('Columns'), 'level_3_columns') ?>
            <?= $this->form->text('level_3_columns', ['level_3_columns' => $level_3_columns], [], [
                'autofocus',
                'tabindex="3"',
                'placeholder="' . t('If empty, time will always be 0') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 4 ' . t('Columns'), 'level_4_columns') ?>
            <?= $this->form->text('level_4_columns', ['level_4_columns' => $level_4_columns], [], [
                'autofocus',
                'tabindex="4"',
                'placeholder="' . t('If empty, time will always be 0') . '"'
            ]) ?>
        </div>

    </div>

    <br>
    <br>


    <!-- LEVEL CAPTIONS -->

    <p>
        <h3><?= t('Levels captions') ?></h3>
    </p>

    <p>
        <?= t('The captions / titles for the columns for the frontend.') ?>
    </p>
    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label('Level 1 ' . t('caption'), 'level_1_caption') ?>
            <?= $this->form->text('level_1_caption', ['level_1_caption' => $level_1_caption], [], [
                'autofocus',
                'tabindex="1"',
                'placeholder="' . t('Leave empty to hide') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 2 ' . t('caption'), 'level_2_caption') ?>
            <?= $this->form->text('level_2_caption', ['level_2_caption' => $level_2_caption], [], [
                'autofocus',
                'tabindex="2"',
                'placeholder="' . t('Leave empty to hide') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 3 ' . t('caption'), 'level_3_caption') ?>
            <?= $this->form->text('level_3_caption', ['level_3_caption' => $level_3_caption], [], [
                'autofocus',
                'tabindex="3"',
                'placeholder="' . t('Leave empty to hide') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 4 ' . t('caption'), 'level_4_caption') ?>
            <?= $this->form->text('level_4_caption', ['level_4_caption' => $level_4_caption], [], [
                'autofocus',
                'tabindex="4"',
                'placeholder="' . t('Leave empty to hide') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('All ' . t('caption'), 'all_caption') ?>
            <?= $this->form->text('all_caption', ['all_caption' => $all_caption], [], [
                'autofocus',
                'tabindex="5"',
                'placeholder="' . t('Leave empty to hide') . '"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 1 ' . t('in dashboard sidebar'), 'dashboard_link_level_1') ?>
            <?= $this->form->checkbox('dashboard_link_level_1', t('enabled'), 1, $dashboard_link_level_1, '', []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 2 ' . t('in dashboard sidebar'), 'dashboard_link_level_1') ?>
            <?= $this->form->checkbox('dashboard_link_level_2', t('enabled'), 1, $dashboard_link_level_2, '', []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 3 ' . t('in dashboard sidebar'), 'dashboard_link_level_1') ?>
            <?= $this->form->checkbox('dashboard_link_level_3', t('enabled'), 1, $dashboard_link_level_3, '', []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label('Level 4 ' . t('in dashboard sidebar'), 'dashboard_link_level_1') ?>
            <?= $this->form->checkbox('dashboard_link_level_4', t('enabled'), 1, $dashboard_link_level_4, '', []) ?>
        </div>

    </div>

    <br>
    <br>


    <!-- IGNORE -->

    <p>
        <h3><?= t('Ignoring subtasks') ?></h3>
    </p>

    <p>
        <?= t('In this section conditions can be defined to make certain subtasks be ignored in the calculations.') ?>
    </p>
    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Subtask has string in its title (multiple with comma seperated)'), 'ignore_subtask_titles') ?>
            <?= $this->form->text('ignore_subtask_titles', ['ignore_subtask_titles' => $ignore_subtask_titles], [], [
                'autofocus',
                'tabindex="1"'
            ]) ?>
        </div>
    </div>

    <br>
    <br>


    <!-- PROGRESS -->

    <p>
        <h3><?= t('Progress') ?></h3>
    </p>

    <p>
        <?= t('Configure the progress (bar) on the task cards.') ?>
    </p>
    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Progress bar on board cards'), 'progressbar_enabled') ?>
            <?= $this->form->checkbox('progressbar_enabled', t('enabled'), 1, $progressbar_enabled, '', [
                'autofocus',
                'tabindex="6"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Progress bar opacity when not started'), 'progressbar_0_opacity') ?>
            <?= $this->form->text('progressbar_0_opacity', ['progressbar_0_opacity' => $progressbar_0_opacity], [], [
                'autofocus',
                'tabindex="7"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Progress bar opacity when started'), 'progressbar_opacity') ?>
            <?= $this->form->text('progressbar_opacity', ['progressbar_opacity' => $progressbar_opacity], [], [
                'autofocus',
                'tabindex="8"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Level or All to use for the project times on home. You can use comma seperated values to included more than one level; e.g.: "level_1,level_3"'), 'progress_home_project_level') ?>
            <?= $this->form->text('progress_home_project_level', ['progress_home_project_level' => $progress_home_project_level], [], [
                'autofocus',
                'tabindex="9"',
                'placeholder="e.g. \'level_1\' or \'all\'"'
            ]) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Hide project times with 0 hours from dashboard'), 'hide_0hours_projects_enabled') ?>
            <?= $this->form->checkbox('hide_0hours_projects_enabled', t('enabled'), 1, $hide_0hours_projects_enabled, '', [
                'autofocus',
                'tabindex="6"'
            ]) ?>
        </div>

    </div>

    <br>
    <br>



    <!-- BLOCK HOURS -->

    <p>
        <h3><?= t('Block hours') ?></h3>
    </p>

    <p>
        <?= t('Configure the block calculations.') ?>
    </p>
    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('How many hours is one block? (0 disables the block feature)'), 'block_hours') ?>
            <?= $this->form->text('block_hours', ['block_hours' => $block_hours], [], [
                'autofocus'
            ]) ?>
        </div>

    </div>

    <br>
    <br>



    <!-- TOOLTIP -->

    <p>
        <h3><?= t('Tooltip') ?></h3>
    </p>

    <p>
        <?= t('Configure the tooltip on the dashboard.') ?>
    </p>
    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Sort by'), 'tooltip_sorting') ?>
            <?= $this->form->select(
                'tooltip_sorting',
                [
                    'id' => 'ID',
                    'remaining_hours_asc' => t('Remaining hours ascending'),
                    'remaining_hours_desc' => t('Remaining hours descending'),
                ],
                ['tooltip_sorting' => $tooltip_sorting],
                [],
                [
                    'autofocus'
                ]
            ) ?>
        </div>

    </div>



    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
