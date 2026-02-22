<div class="page-header">
    <h2><?= t('Do you really want to clean tasks in this column?') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'cleanTasksInColumn', array('project_id' => $project['id'], 'plugin' => 'WeekHelper')) ?>">
    <?= $this->form->csrf() ?>
    <?= $this->form->hidden('column_id', $values) ?>
    <?= $this->form->hidden('swimlane_id', $values) ?>

    <p class="alert"><?= t('It will remove all done subtasks of %d tasks in the column "%s" and the swimlane "%s". In non-time-mode this will also set their score to 0.', $nb_tasks, $column, $swimlane) ?></p>

    <?= $this->modal->submitButtons(array(
        'submitLabel' => t('Yes'),
        'color' => 'red',
    )) ?>
</form>
