<span class="dropdown">
    <a href="#" class="dropdown-menu"><i class="fa fa-wrench"></i></i></a>
    <ul>
        <li>
            <?= $this->modal->confirm('check', t('Clean tasks in column'), 'WeekHelperController', 'cleanTasksInColumnConfirm', array('project_id' => $column['project_id'], 'column_id' => $column['id'], 'swimlane_id' => $swimlane['id'], 'plugin' => 'WeekHelper')) ?>
        </li>
    </ul>
</span>
