<?php
    $this->hoursViewHelper->initTasks('search');
    $times = $this->hoursViewHelper->getTimes();
?>

<table>
    <tr>
        <td>
            <span class="thv-weak-color">All:</span>
        </td>

        <td>
            <span class="thv-title-color">
                <?= t('Estimated'); ?>:
            </span>
            <span class="thv-estimated-color">
                <?= $times->getEstimatedTotal(true) ?>h
            </span>
        </td>

        <td>
            <span class="thv-title-color">
                <?= t('Spent'); ?>:
            </span>
            <span class="thv-spent-color">
                <?= $times->getSpentTotal(true) ?>h
                <i class="thv-font-small">
                    (<?= $this->hoursViewHelper->getOvertimeInfo($times->getSpentTotal(), $times->getOvertimeTotal()); ?>)
                </i>
            </span>
        </td>

        <td>
            <span class="thv-title-color">
                <?= t('Remaining'); ?>:
            </span>
            <span class="thv-remaining-color">
                <?= $times->getRemainingTotal(true) ?>h
            </span>
        </td>
    </tr>
</table>


<div class="table-list">
    <?= $this->render('task_list/header', array(
        'paginator' => $paginator,
    )) ?>

    <?php foreach ($paginator->getCollection() as $task): ?>
        <div class="table-list-row color-<?= $task['color_id'] ?>">
            <?= $this->render('task_list/task_title', array(
                'task' => $task,
            )) ?>

            <?= $this->render('task_list/task_details', array(
                'task' => $task,
            )) ?>

            <?= $this->render('task_list/task_avatars', array(
                'task' => $task,
            )) ?>

            <?= $this->render('task_list/task_icons', array(
                'task' => $task,
            )) ?>

            <?= $this->render('task_list/task_subtasks', array(
                'task' => $task,
            )) ?>

            <?= $this->hook->render('template:search:task:footer', array('task' => $task)) ?>
        </div>
    <?php endforeach ?>
</div>

<?= $paginator ?>