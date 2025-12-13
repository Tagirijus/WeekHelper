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
            'block_hours' => $this->configModel->get('hoursview_block_hours', 0),
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
            'hoursview_ignore_subtask_titles' => $form['ignore_subtask_titles'],
            'hoursview_progressbar_enabled' => isset($form['progressbar_enabled']) ? 1 : 0,
            'hoursview_progressbar_opacity' => $form['progressbar_opacity'],
            'hoursview_progressbar_0_opacity' => $form['progressbar_0_opacity'],
            'hoursview_progress_home_project_level' => $form['progress_home_project_level'],
            'hoursview_hide_0hours_projects_enabled' => isset($form['hide_0hours_projects_enabled']) ? 1 : 0,
            'hoursview_block_hours' => $form['block_hours'],
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
        $this->logger->info(json_encode($level));
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
            'block_hours' => $this->configModel->get('hoursview_block_hours', 0),
            'tooltip_sorting' => $this->configModel->get('hoursview_tooltip_sorting', 'id')
        ]));
    }

    /**
     * Get the tooltip for the task times.
     *
     * @return HTML
     */
    public function getTooltipTaskTimes()
    {
        $task_id = $this->request->getStringParam('task_id', -1);
        $times = $this->helper->hoursViewHelper->getTimesForAllActiveProjects();
        $this->response->html($this->template->render('WeekHelper:tooltips/tooltip_task_times', [
            'task_id' => $task_id,
            'times' => $times
        ]));
    }

    /**
     * Get the tooltip for the hours header, showing
     * the worked times for done task for: days, weeks
     * and months.
     *
     * @return HTML
     */
    public function getTooltipWorkedTimes()
    {
        $project_id = $this->request->getStringParam('project_id', -1);
        $month_times = [
            -1 => $this->helper->hoursViewHelper->getMonthTimes($project_id, -1),
            0 => $this->helper->hoursViewHelper->getMonthTimes($project_id, 0)
        ];
        $week_times = [
            -1 => $this->helper->hoursViewHelper->getWeekTimes($project_id, -1),
            0 => $this->helper->hoursViewHelper->getWeekTimes($project_id, 0)
        ];
        $day_times = [
            -2 => $this->helper->hoursViewHelper->getDayTimes($project_id, -2),
            -1 => $this->helper->hoursViewHelper->getDayTimes($project_id, -1),
            0 => $this->helper->hoursViewHelper->getDayTimes($project_id, 0)
        ];

        $this->response->html($this->template->render('WeekHelper:tooltips/tooltip_worked_times', [
            'project_id' => $project_id,
            'month_times' => $month_times,
            'week_times' => $week_times,
            'day_times' => $day_times
        ]));
    }

    /**
     * Get the automatic planned week as plaintext, HTML or JSON.
     * TODO: HTML + JSON; with "?type=html" or "?type=json"
     *
     * @return Response
     */
    public function getAutomaticPlan()
    {
        $type = $this->request->getStringParam('type', 'text');
        if ($type == 'html') {
            // TODO
            // $automatic_plan = $this->helper->automaticPlanner->getAutomaticPlanAsHTML();
            $automatic_plan = 'TODO HTML';
        } elseif ($type == 'json') {
            // TODO
            // $automatic_plan = $this->helper->automaticPlanner->getAutomaticPlanAsJSON();
            $automatic_plan = 'TODO JSON';
        } else {
            $automatic_plan = $this->helper->automaticPlanner->getAutomaticPlanAsText();
        }
        return $this->response->text($automatic_plan);
    }
}