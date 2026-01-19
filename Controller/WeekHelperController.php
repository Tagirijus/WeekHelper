<?php

namespace Kanboard\Plugin\WeekHelper\Controller;




class WeekHelperController extends \Kanboard\Controller\PluginController
{
    /**
     * Show one of the settings pages of the WeekHelper plugin.
     *
     * @return HTML response
     */
    public function showConfigWeeks()
    {
        // !!!!!
        // When I want to add new config options, I also have to add them
        // in the WeekHelperHelper.php in the getConfig() Method !
        // !!!!!
        $this->response->html($this->helper->layout->config('WeekHelper:config/weekhelper_configWeeks', $this->helper->weekHelperHelper->getConfig()));
    }

    /**
     * Show one of the settings pages of the WeekHelper plugin.
     *
     * @return HTML response
     */
    public function showConfigHoursView()
    {
        // !!!!!
        // When I want to add new config options, I also have to add them
        // in the WeekHelperHelper.php in the getConfig() Method !
        // !!!!!
        $this->response->html($this->helper->layout->config('WeekHelper:config/weekhelper_configHoursView', $this->helper->weekHelperHelper->getConfig()));
    }

    /**
     * Show one of the settings pages of the WeekHelper plugin.
     *
     * @return HTML response
     */
    public function showConfigRemainingBox()
    {
        // !!!!!
        // When I want to add new config options, I also have to add them
        // in the WeekHelperHelper.php in the getConfig() Method !
        // !!!!!
        $this->response->html($this->helper->layout->config('WeekHelper:config/weekhelper_configRemainingBox', $this->helper->weekHelperHelper->getConfig()));
    }

    /**
     * Show one of the settings pages of the WeekHelper plugin.
     *
     * @return HTML response
     */
    public function showConfigAutomaticPlanner()
    {
        // !!!!!
        // When I want to add new config options, I also have to add them
        // in the WeekHelperHelper.php in the getConfig() Method !
        // !!!!!
        $this->response->html($this->helper->layout->config('WeekHelper:config/weekhelper_configAutomaticPlanner', $this->helper->weekHelperHelper->getConfig()));
    }

    /**
     * Show one of the settings pages of the WeekHelper plugin.
     *
     * @return HTML response
     */
    public function showConfigTimetagger()
    {
        // !!!!!
        // When I want to add new config options, I also have to add them
        // in the WeekHelperHelper.php in the getConfig() Method !
        // !!!!!
        $this->response->html($this->helper->layout->config('WeekHelper:config/weekhelper_configTimetagger', $this->helper->weekHelperHelper->getConfig()));
    }

    /**
     * Show one of the levels as a new page, instead of just a tooltip
     * like on the dashboard hovers.
     *
     * @return HTML response
     */
    public function showLevelHoverAsPage()
    {
        $user = $this->getUser();
        $level = $this->request->getStringParam('level', 'all');
        if ($level == 'level_1') {
            $label = $this->configModel->get('hoursview_level_1_caption', 'level_1');
        } elseif ($level == 'level_2') {
            $label = $this->configModel->get('hoursview_level_2_caption', 'level_2');
        } elseif ($level == 'level_3') {
            $label = $this->configModel->get('hoursview_level_3_caption', 'level_3');
        } elseif ($level == 'level_4') {
            $label = $this->configModel->get('hoursview_level_4_caption', 'level_4');
        } else {
            $label = $this->configModel->get('hoursview_all_caption', 'all');
        }
        $times = $this->helper->hoursViewHelper->getTimesForAllActiveProjects();

        $this->response->html($this->helper->layout->dashboard('WeekHelper:tooltips/tooltip_dashboard_times', [
            'title' => 'Level: ' . $label,
            'user' => $user,
            'label' => $label,
            'level' => $level,
            'times' => $times,
            'tooltip_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id')
        ]));
    }

    /**
     * Save the setting for WeekHelper Weeks.
     */
    public function saveConfigWeeks()
    {
        $form = $this->request->getValues();

        $values = [
            'weekhelper_headerdate_enabled' => isset($form['headerdate_enabled']) ? 1 : 0,
            'weekhelper_week_pattern' => $form['week_pattern'],
            'weekhelper_time_box_enabled' => isset($form['time_box_enabled']) ? 1 : 0,
            'weekhelper_due_date_week_card_enabled' => isset($form['due_date_week_card_enabled']) ? 1 : 0,
            'weekhelper_full_start_date_enabled' => isset($form['full_start_date_enabled']) ? 1 : 0,
            'weekhelper_due_date_week_list_enabled' => isset($form['due_date_week_list_enabled']) ? 1 : 0,
            'weekhelper_calendarweeks_for_week_difference_enabled' => isset($form['calendarweeks_for_week_difference_enabled']) ? 1 : 0,
            'weekhelper_block_icon_before_task_title' => isset($form['block_icon_before_task_title']) ? 1 : 0,
            'weekhelper_block_ignore_columns' => $form['block_ignore_columns'],
            'weekhelper_todo_icon_before_task_title' => isset($form['todo_icon_before_task_title']) ? 1 : 0,
        ];

        $this->languageModel->loadCurrentLanguage();

        if ($this->configModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        return $this->response->redirect($this->helper->url->to('WeekHelperController', 'showConfigWeeks', ['plugin' => 'WeekHelper']), true);
    }

    /**
     * Save the setting for WeekHelper HoursView.
     */
    public function saveConfigHoursView()
    {
        $form = $this->request->getValues();

        $values = [
            'hoursview_non_time_mode_minutes' => $form['non_time_mode_minutes'],
            'hoursview_level_1_columns' => $form['level_1_columns'],
            'hoursview_level_2_columns' => $form['level_2_columns'],
            'hoursview_level_3_columns' => $form['level_3_columns'],
            'hoursview_level_4_columns' => $form['level_4_columns'],
            'hoursview_level_1_caption' => $form['level_1_caption'],
            'hoursview_level_2_caption' => $form['level_2_caption'],
            'hoursview_level_3_caption' => $form['level_3_caption'],
            'hoursview_level_4_caption' => $form['level_4_caption'],
            'hoursview_all_caption' => $form['all_caption'],
            'hoursview_progressbar_enabled' => isset($form['progressbar_enabled']) ? 1 : 0,
            'hoursview_progressbar_opacity' => $form['progressbar_opacity'],
            'hoursview_progressbar_0_opacity' => $form['progressbar_0_opacity'],
            'hoursview_progress_home_project_level' => $form['progress_home_project_level'],
            'hoursview_hide_0hours_projects_enabled' => isset($form['hide_0hours_projects_enabled']) ? 1 : 0,
            'hoursview_tooltip_sorting' => $form['tooltip_sorting'],
            'hoursview_dashboard_link_level_1' => $form['dashboard_link_level_1'],
            'hoursview_dashboard_link_level_2' => $form['dashboard_link_level_2'],
            'hoursview_dashboard_link_level_3' => $form['dashboard_link_level_3'],
            'hoursview_dashboard_link_level_4' => $form['dashboard_link_level_4'],
            'hoursview_dashboard_link_level_all' => $form['dashboard_link_level_all'],
        ];

        $this->languageModel->loadCurrentLanguage();

        if ($this->configModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        return $this->response->redirect($this->helper->url->to('WeekHelperController', 'showConfigHoursView', ['plugin' => 'WeekHelper']), true);
    }

    /**
     * Save the setting for WeekHelper remaining box.
     */
    public function saveConfigRemainingBox()
    {
        $form = $this->request->getValues();

        $values = [
            'weekhelper_remaining_days_enabled' => isset($form['remaining_days_enabled']) ? 1 : 0,
            'weekhelper_remaining_lvl_days' => $form['remaining_lvl_days'],
            'weekhelper_remaining_weeks_enabled' => isset($form['remaining_weeks_enabled']) ? 1 : 0,
            'weekhelper_remaining_lvl_weeks' => $form['remaining_lvl_weeks'],
        ];

        $this->languageModel->loadCurrentLanguage();

        if ($this->configModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        return $this->response->redirect($this->helper->url->to('WeekHelperController', 'showConfigRemainingBox', ['plugin' => 'WeekHelper']), true);
    }

    /**
     * Save the setting for WeekHelper automatic planner.
     */
    public function saveConfigAutomaticPlanner()
    {
        $form = $this->request->getValues();

        $values = [
            'weekhelper_sorting_logic' => $form['sorting_logic'],
            'weekhelper_automatic_planner_sticky_enabled' => isset($form['automatic_planner_sticky_enabled']) ? 1 : 0,
            'weekhelper_level_active_week' => $form['level_active_week'],
            'weekhelper_level_planned_week' => $form['level_planned_week'],
            'weekhelper_monday_slots' => $form['monday_slots'],
            'weekhelper_tuesday_slots' => $form['tuesday_slots'],
            'weekhelper_wednesday_slots' => $form['wednesday_slots'],
            'weekhelper_thursday_slots' => $form['thursday_slots'],
            'weekhelper_friday_slots' => $form['friday_slots'],
            'weekhelper_saturday_slots' => $form['saturday_slots'],
            'weekhelper_sunday_slots' => $form['sunday_slots'],
            'weekhelper_minimum_slot_length' => $form['minimum_slot_length'],
            'weekhelper_block_active_week' => $form['block_active_week'],
            'weekhelper_block_planned_week' => $form['block_planned_week'],
            'weekhelper_caldav_user' => $form['caldav_user'],
            'weekhelper_caldav_app_pwd' => $form['caldav_app_pwd'],
            'weekhelper_calendar_urls' => $form['calendar_urls'],
        ];

        $this->languageModel->loadCurrentLanguage();

        if ($this->configModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        return $this->response->redirect($this->helper->url->to('WeekHelperController', 'showConfigAutomaticPlanner', ['plugin' => 'WeekHelper']), true);
    }

    /**
     * Save the setting for WeekHelper Timetagger API.
     */
    public function saveConfigTimetagger()
    {
        $form = $this->request->getValues();

        $values = [
            'timetagger_url' => $form['timetagger_url'],
            'timetagger_authtoken' => $form['timetagger_authtoken'],
            'timetagger_cookies' => $form['timetagger_cookies'],
            'timetagger_overwrites_levels_spent' => $form['timetagger_overwrites_levels_spent'],
            'timetagger_start_fetch' => $form['timetagger_start_fetch'],
        ];

        $this->languageModel->loadCurrentLanguage();

        if ($this->configModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        return $this->response->redirect($this->helper->url->to('WeekHelperController', 'showConfigTimetagger', ['plugin' => 'WeekHelper']), true);
    }

    /**
     * Get the config for the week pattern as JSON for the javascript.
     *
     * @return Response
     */
    public function getWeekPattern()
    {
        $weekPattern = $this->configModel->get('weekhelper_week_pattern', '{YEAR_SHORT}W{WEEK}');
        return $this->response->text($weekPattern);
    }

    /**
     * Get the tooltip for the dashboard times.
     *
     * @return HTML
     */
    public function getTooltipDashboardTimes()
    {
        $level = $this->request->getStringParam('level', 'all');
        if ($level == 'level_1') {
            $label = $this->configModel->get('hoursview_level_1_caption', 'level_1');
        } elseif ($level == 'level_2') {
            $label = $this->configModel->get('hoursview_level_2_caption', 'level_2');
        } elseif ($level == 'level_3') {
            $label = $this->configModel->get('hoursview_level_3_caption', 'level_3');
        } elseif ($level == 'level_4') {
            $label = $this->configModel->get('hoursview_level_4_caption', 'level_4');
        } else {
            $label = $this->configModel->get('hoursview_all_caption', 'all');
        }
        $times = $this->helper->hoursViewHelper->getTimesForAllActiveProjects();
        $this->response->html($this->template->render('WeekHelper:tooltips/tooltip_dashboard_times', [
            'label' => $label,
            'level' => $level,
            'times' => $times,
            'tooltip_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id')
        ]));
    }

    /**
     * Get the automatic planned week as plaintext or JSON string.
     *
     * @return Response
     */
    public function getAutomaticPlan()
    {
        $type = $this->request->getStringParam('type', 'text');
        if ($type == 'json') {
            $automatic_plan = json_encode($this->automaticPlanner->getAutomaticPlanAsArray(
                $this->request->getStringParam('add_blocking', 0) == 1
            ));
        } else {
            $automatic_plan = $this->automaticPlanner->getAutomaticPlanAsText(
                [
                    'week_only' => $this->request->getStringParam('week_only', ''),
                    'days' => $this->request->getStringParam('days', 'mon,tue,wed,thu,fri,sat,sun,overflow,ovr'),
                    'hide_times' => $this->request->getStringParam('hide_times', 0) == 1,
                    'hide_length' => $this->request->getStringParam('hide_length', 0) == 1,
                    'hide_task_title' => $this->request->getStringParam('hide_task_title', 0) == 1,
                    'prepend_project_name' => $this->request->getStringParam('prepend_project_name', 0) == 1,
                    'prepend_project_alias' => $this->request->getStringParam('prepend_project_alias', 0) == 1,
                    'show_day_planned' => $this->request->getStringParam('show_day_planned', 0) == 1,
                    'show_week_times' => $this->request->getStringParam('show_week_times', 0) == 1,
                    'add_blocking' => $this->request->getStringParam('add_blocking', 0) == 1
                ]
            );
        }
        return $this->response->text($automatic_plan);
    }

    /**
     * This function will update the blocking tasks / timeslots
     * from the CalDAV calendar/s from the config.
     *
     * Output is success message or info about fail.
     *
     * @return Response
     */
    public function updateBlockingTasks()
    {
        $success = $this->automaticPlanner->updateBlockingTasks();

        $this->languageModel->loadCurrentLanguage();

        if ($success === true) {
            $this->flash->success(t('Blocking tasks update successful'));
        } else {
            $this->flash->failure(t($success));
        }

        return $this->response->redirect($this->request->getUri(), true);
    }
}