<div class="page-header">
    <h2><?= t('WeekHelper automatic planner configuration') ?></h2>
</div>
<form method="post" action="<?= $this->url->href('WeekHelperController', 'saveConfigAutomaticPlanner', ['plugin' => 'WeekHelper']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <br>

    <!-- Blocking slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <b>Blocking</b>
            <p class="weekhelper-config-weak-text">
                <?= t('Here you can assign slots with "day + whitespace + timespan + whitespace + title", which will be used to "deplete" time slots. This way you can have a normal week plan with slots (below on this page), but still have e.g. private dates for the active or next (planned) week without the need to modify the week plan every week. The idea is that these two text areas can be modified automatically from a private CalDAV calendar later or so.') ?>
            </p>
            <?= $this->form->label(t('Blocking for active week'), 'block_active_week') ?>
            <?= $this->form->textarea('block_active_week', ['block_active_week' => $block_active_week], [], [
                "placeholder='mon 9:00-10:00 dentist\nwed 0:00-24:00 vacation\nthu 13:00-24:00 closing time'"
            ], 'weekhelper-textarea-config') ?>
            <?= $this->form->label(t('Blocking for next/planned week'), 'block_planned_week') ?>
            <?= $this->form->textarea('block_planned_week', ['block_planned_week' => $block_planned_week], [], [
                "placeholder='mon 9:00-10:00 dentist\nwed 0:00-24:00 vacation\nthu 13:00-24:00 closing time'"
            ], 'weekhelper-textarea-config') ?>
            &nbsp;
            <p>
                <a href="/weekhelper/updateblockingtasks" class="js-modal-small">Update from CalDAV</a>
            </p>
            &nbsp;
        </div>
    </div>

    <br>

    <div class="task-form-container">

        <div class="task-form-main-column">
            <b>Calendar sync</b>
            <p class="weekhelper-config-weak-text">
                <?= t('Here you can set up the config for getting blocking timeslots / tasks from a CalDAV calendar. User and app-password are needed (at the moment only for one user, sorry!). In the calendar-urls textarea you can have multiple calendar urls in each line, using the credentials with the user and app-password.') ?>
            </p>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('CalDAV User'), 'caldav_user') ?>
            <?= $this->form->text('caldav_user', ['caldav_user' => $caldav_user], [], []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('CalDAV App-Pwd'), 'caldav_app_pwd') ?>
            <?= $this->form->text('caldav_app_pwd', ['caldav_app_pwd' => $caldav_app_pwd], [], []) ?>
        </div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Calendar URLs'), 'calendar_urls') ?>
            <?= $this->form->textarea('calendar_urls', ['calendar_urls' => $calendar_urls], [], [], 'weekhelper-textarea-config') ?>
        </div>

    </div>

    <br>
    <br>


    <!-- Sorting logic -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Sorting logic'), 'sorting_logic') ?>
            <p class="weekhelper-config-weak-text">
                <?= t('This is the core sorting logic for the automatic planner feature. Here you can define a sorting "column" (of a task) with asc/desc per line (column + whitespace + direction). Some important available columns are: project_priority, project_wage, project_max_hours, project_type, priority, column_position, position, score. All "project_" keys are from my plugin. Further columns are the one from a native Kanbaord task array (see source code if in doubt).') ?>
            </p>
            <?= $this->form->textarea('sorting_logic', ['sorting_logic' => $sorting_logic], [], [
                "placeholder='priority desc\ncolumn_pos desc\nposition asc'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>

    <br>
    <br>


    <!-- Enable sticky planner -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <?= $this->form->label(t('Show sticky week plan'), 'automatic_planner_sticky_enabled') ?>
            <?= $this->form->checkbox('automatic_planner_sticky_enabled', t('enabled'), 1, $automatic_planner_sticky_enabled) ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t('Show a sticky div on the screen, which contains the automatic week planning.') ?>
    </p>

    <br>
    <br>

    <span>Active / planned week</span>


    <!-- which levels are planned vs. active week? -->

    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t('Technically the automatic planner will plan the task form the "active week" into the actual running week and the task from the "planned week" onto the next week. Tell the system which level stands for which of those weeks.') ?>
    </p>

    <div class="task-form-container">
        <div class="task-form-main-column">
            <?= $this->form->label(t('Level to use for the active week.'), 'level_active_week') ?>
            <?= $this->form->text('level_active_week', ['level_active_week' => $level_active_week], [], [
                'placeholder="e.g. \'level_1\' or \'all\'"'
            ]) ?>
        </div>
    </div>

    <div class="task-form-container">
        <div class="task-form-main-column">
            <?= $this->form->label(t('Level to use for the planned week.'), 'level_planned_week') ?>
            <?= $this->form->text('level_planned_week', ['level_planned_week' => $level_planned_week], [], [
                'placeholder="e.g. \'level_1\' or \'all\'"'
            ]) ?>
        </div>
    </div>


    <!-- time slots help text -->
    <?php $slots_help = 'A slot should contain 24h format starting and end times (e.g. "6:00-9:00"). It can also contain a condition after that times, separated with a single whitespace and in the format "key:value", which would represent a tasks array condition. E.g. you can have only tasks with the category_name "General" on that slot (e.g. "6:00-9:00 category_name:General"), or all tasks without the category_name "Music" on that slot by using a negatvion sign before the value (e.g. "6:00-9:00 category_name:!Music"). Also you can have multiple conditions separated by a whitespace. Possible task array keys are below.'; ?>


    <!-- Monday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Monday time slots'), 'monday_slots') ?>
            <?= $this->form->textarea('monday_slots', ['monday_slots' => $monday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Tuesday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Tuesday time slots'), 'tuesday_slots') ?>
            <?= $this->form->textarea('tuesday_slots', ['tuesday_slots' => $tuesday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Wednesday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Wednesday time slots'), 'wednesday_slots') ?>
            <?= $this->form->textarea('wednesday_slots', ['wednesday_slots' => $wednesday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Thursday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Thursday time slots'), 'thursday_slots') ?>
            <?= $this->form->textarea('thursday_slots', ['thursday_slots' => $thursday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Friday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Friday time slots'), 'friday_slots') ?>
            <?= $this->form->textarea('friday_slots', ['friday_slots' => $friday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Saturday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Saturday time slots'), 'saturday_slots') ?>
            <?= $this->form->textarea('saturday_slots', ['saturday_slots' => $saturday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>


    <!-- Sunday time slots -->

    <div class="task-form-container">

        <div class="task-form-main-column">&nbsp;</div>

        <div class="task-form-main-column">
            <?= $this->form->label(t('Sunday time slots'), 'sunday_slots') ?>
            <?= $this->form->textarea('sunday_slots', ['sunday_slots' => $sunday_slots], [], [
                "placeholder='6:00-9:00 office\n11:00-13:00 studio'"
            ], 'weekhelper-textarea-config') ?>
        </div>

    </div>
    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t($slots_help) ?>
    </p>

    <br>
    <br>



    <!-- Available task keys -->

    <div class="task-form-container">

        <div class="task-form-main-column">
            <b>Task Keys</b>
        </div>

        <div class="task-form-main-column">
            assignee_avatar_path - assignee_email - assignee_name - assignee_username - category_color_id - category_description - category_id - category_name - color_id - column_id - column_name - column_position - creator_id - date_completed - date_creation - date_due - date_modification - date_moved - date_started - description - id - is_active - is_milestone - levels - nb_comments - nb_completed_subtasks - nb_external_links - nb_files - nb_links - nb_subtasks - open_subtasks - owner_id - plan_from - position - priority - project_alias - project_id - project_max_hours_block - project_max_hours_day - project_max_hours_fri - project_max_hours_mon - project_max_hours_sat - project_max_hours_sun - project_max_hours_thu - project_max_hours_tue - project_max_hours_wed - project_name - project_priority - project_type - project_wage - recurrence_basedate - recurrence_child - recurrence_factor - recurrence_parent - recurrence_status - recurrence_timeframe - recurrence_trigger - reference - score - swimlane_id - swimlane_name - time_estimated - time_overtime - time_remaining - time_spent - timetagger_tags - title
        </div>

    </div>

    <br>
    <br>



    <!-- minimum block length -->

    <p class="task-form-main-column weekhelper-config-weak-text">
        <?= t('While planing tasks into time slots, time slots get depleted, technically. This is the minimum amount of minutes a time slot should have available for a task to be planned on it. Otherwise the time slot gets depleted automatically without any task planned. This way I can have some "minimum block length" for task to be planned on. (Disabled with anything < 1)') ?>
    </p>

    <div class="task-form-container">
        <div class="task-form-main-column">
            <?= $this->form->label(t('Minimum slot length in minutes'), 'minimum_slot_length') ?>
            <?= $this->form->text('minimum_slot_length', ['minimum_slot_length' => $minimum_slot_length], [], []) ?>
        </div>
    </div>


    <div class="task-form-bottom">
        <?= $this->modal->submitButtons() ?>
    </div>

</form>
