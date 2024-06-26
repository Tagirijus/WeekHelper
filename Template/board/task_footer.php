<?php

$hoursview_config = $this->hoursViewHelper->getConfig();

?>

<div class="wh-category-tag-wrapper">

    <?php if (! empty($task['tags'])): ?>
        <div class="task-tags">
            <ul>
            <?php foreach ($task['tags'] as $tag): ?>
                <li class="task-tag <?= $tag['color_id'] ? "color-{$tag['color_id']}" : '' ?>"><?= $this->text->e($tag['name']) ?></li>
            <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <?php if (! empty($task['category_id'])): ?>
    <div class="task-board-category-container task-board-category-container-color">
        <span class="task-board-category category-<?= $this->text->e($task['category_name']) ?> <?= $task['category_color_id'] ? "color-{$task['category_color_id']}" : '' ?>">
            <?php if ($not_editable): ?>
                <?= $this->text->e($task['category_name']) ?>
            <?php else: ?>
                <?= $this->url->link(
                    $this->text->e($task['category_name']),
                    'TaskModificationController',
                    'edit',
                    array('task_id' => $task['id']),
                    false,
                    'js-modal-large' . (! empty($task['category_description']) ? ' tooltip' : ''),
                    t('Change category')
                ) ?>
                <?php if (! empty($task['category_description'])): ?>
                    <?= $this->app->tooltipMarkdown($task['category_description']) ?>
                <?php endif ?>
            <?php endif ?>
        </span>
    </div>
    <?php endif ?>

</div>


<div class="task-board-icons">
    <div class="task-board-icons-row">
        <?php if ($task['reference']): ?>
            <span class="task-board-reference" title="<?= t('Reference') ?>">
                <span class="ui-helper-hidden-accessible"><?= t('Reference') ?> </span><?= $this->task->renderReference($task) ?>
            </span>
        <?php endif ?>
    </div>
    <div class="task-board-icons-row">
        <?php if ($task['is_milestone'] == 1): ?>
            <span title="<?= t('Milestone') ?>">
                <i class="fa fa-flag flag-milestone" role="img" aria-label="<?= t('Milestone') ?>"></i>
            </span>
        <?php endif ?>

        <?php if ($task['score']): ?>
            <span class="task-score" title="<?= t('Complexity') ?>">
                <i class="fa fa-trophy" role="img" aria-label="<?= t('Complexity') ?>"></i>
                <?= $this->text->e($task['score']) ?>
            </span>
        <?php endif ?>

        <?php if (! empty($task['time_estimated']) || ! empty($task['time_spent'])): ?>
            <span class="task-time-estimated" title="<?= t('Time remaining, spent and estimated') ?>">
                <span class="ui-helper-hidden-accessible"><?= t('Time remaining, spent and estimated') ?> </span><?= $this->text->e($this->hoursViewHelper->floatToHHMM($this->hoursViewHelper->getRemainingTimeForTask($task))) ?>h
                    <i>(<?= $this->text->e($this->hoursViewHelper->floatToHHMM($task['time_spent'])) ?>h / <?= $this->text->e($this->hoursViewHelper->floatToHHMM($task['time_estimated'])) ?>h)</i>
            </span>
        <?php endif ?>

    </div>
    <div class="task-board-icons-row">

        <?php if ($task['recurrence_status'] == \Kanboard\Model\TaskModel::RECURRING_STATUS_PENDING): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-refresh fa-rotate-90"></i>', $this->url->href('BoardTooltipController', 'recurrence', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if ($task['recurrence_status'] == \Kanboard\Model\TaskModel::RECURRING_STATUS_PROCESSED): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-refresh fa-rotate-90 fa-inverse"></i>', $this->url->href('BoardTooltipController', 'recurrence', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if (! empty($task['nb_links'])): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-code-fork fa-fw"></i>'.$task['nb_links'], $this->url->href('BoardTooltipController', 'tasklinks', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if (! empty($task['nb_external_links'])): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-external-link fa-fw"></i>'.$task['nb_external_links'], $this->url->href('BoardTooltipController', 'externallinks', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if (! empty($task['nb_subtasks'])): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-bars fa-fw"></i>'.round($task['nb_completed_subtasks'] / $task['nb_subtasks'] * 100, 0).'%', $this->url->href('BoardTooltipController', 'subtasks', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if (! empty($task['nb_files'])): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-paperclip fa-fw"></i>'.$task['nb_files'], $this->url->href('BoardTooltipController', 'attachments', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if ($task['nb_comments'] > 0): ?>
            <?php if ($not_editable): ?>
                <?php $aria_label = $task['nb_comments'] == 1 ? t('%d comment', $task['nb_comments']) : t('%d comments', $task['nb_comments']); ?>
                <span title="<?= $aria_label ?>" role="img" aria-label="<?= $aria_label ?>"><i class="fa fa-comments-o"></i>&nbsp;<?= $task['nb_comments'] ?></span>
            <?php else: ?>
                <?= $this->modal->medium(
                    'comments-o',
                    $task['nb_comments'],
                    'CommentListController',
                    'show',
                    array('task_id' => $task['id']),
                    $task['nb_comments'] == 1 ? t('%d comment', $task['nb_comments']) : t('%d comments', $task['nb_comments'])
                ) ?>
            <?php endif ?>
        <?php endif ?>

        <?php if (! empty($task['description'])): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-file-text-o"></i>', $this->url->href('BoardTooltipController', 'description', array('task_id' => $task['id']))) ?>
        <?php endif ?>

        <?php if ($task['is_active'] == 1): ?>
            <div class="task-icon-age">
                <span title="<?= t('Task age in days')?>" class="task-icon-age-total"><span class="ui-helper-hidden-accessible"><?= t('Task age in days') ?> </span><?= $this->dt->age($task['date_creation']) ?></span>
                <span title="<?= t('Days in this column')?>" class="task-icon-age-column"><span class="ui-helper-hidden-accessible"><?= t('Days in this column') ?> </span><?= $this->dt->age($task['date_moved']) ?></span>
            </div>
        <?php else: ?>
            <span class="task-board-closed"><i class="fa fa-ban fa-fw"></i><?= t('Closed') ?></span>
        <?php endif ?>

        <?= $this->task->renderPriority($task['priority']) ?>


        <!-- Task Progress Bar -->

        <?php if ($task['time_estimated'] > 0 && $hoursview_config['progressbar_enabled'] == 1): ?>

            <?php
                $percent = $this->hoursViewHelper->getPercentForTask($task);
                $percent_txt = $percent;
                $percent_opacity = $hoursview_config['progressbar_opacity'];
                if ($percent > 100) {
                    $percent = 100;
                } elseif ($percent == 0) {
                    $percent_opacity = $hoursview_config['progressbar_0_opacity'];
                }
            ?>

            <div class="container-task-progress-bar" style="opacity: <?= $percent_opacity; ?>;">
                <div class="task-progress-bar <?= $this->hoursViewHelper->getPercentCSSClass($percent, $task); ?>" style="width:<?= $percent . '%'; ?>;">
                    <?= $percent_txt . '%' ?>
                </div>
            </div>

        <?php endif ?>

        <?= $this->hook->render('template:board:task:icons', array('task' => $task)) ?>

    </div>
</div>


<!-- DATES -->

<?php if ($task['date_started'] != 0 || $task['date_due'] != 0): ?>

    <div class="task-board-icons">
        <div class="task-board-icons-row" style="margin-top: -.5em; margin-bottom: -.5em;">

            <!-- Started Date -->
            <?php if ($task['date_started'] != 0): ?>
                <span title="<?= t('Start Date') ?>">
                    <i class="fa fa-play" role="img" aria-label="<?= t('Start Date') ?>"></i>
                    <?php if ($this->weekHelperHelper->showFullStartedDateOnCard()): ?>
                        <?= $this->dt->date($task['date_started']) ?>
                    <?php endif ?>
                </span>
            <?php endif ?>

            <!-- Due Date -->
            <?php if ($task['date_due'] != 0): ?>
                <span class="task-date
                    <?php if (time() > $task['date_due']): ?>
                         task-date-overdue
                    <?php elseif (date('Y-m-d') == date('Y-m-d', $task['date_due'])): ?>
                         task-date-today
                    <?php endif ?>
                    ">
                    <i class="fa fa-calendar"></i>
                    <?php if (date('Hi', $task['date_due']) === '0000' ): ?>
                        <?= $this->dt->date($task['date_due']) ?>
                    <?php else: ?>
                        <?= $this->dt->datetime($task['date_due']) ?>
                    <?php endif ?>

                    <?= $this->weekHelperHelper->showWeekOfDueDateOnCard($task['date_due']) ?>

                </span>

                <!-- Remaining box -->
                <?= $this->render('WeekHelper:remaining_box', ['task' => $task]) ?>

            <?php endif ?>

        </div>
    </div>

<?php endif ?>



<?= $this->hook->render('template:board:task:footer', array('task' => $task)) ?>
