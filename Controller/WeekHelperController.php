<?php

namespace Kanboard\Plugin\WeekHelper\Controller;




class WeekHelperController extends \Kanboard\Controller\PluginController
{
    /**
     * Settins page for the WeekHelper plugin.
     *
     * @return HTML response
     */
    public function show()
    {
        // !!!!!
        // When I want to add new config options, I also have to add them
        // in the WeekHelperHelper.php in the getConfig() Method !
        // !!!!!
        $this->response->html($this->helper->layout->config('WeekHelper:config/weekhelper_config', $this->helper->weekHelperHelper->getConfig()));
    }

    /**
     * Save the setting for WeekHelper.
     */
    public function saveConfig()
    {
        $form = $this->request->getValues();

        $values = [
            'weekhelper_headerdate_enabled' => isset($form['headerdate_enabled']) ? 1 : 0,
            'weekhelper_week_pattern' => $form['week_pattern'],
            'weekhelper_time_box_enabled' => isset($form['time_box_enabled']) ? 1 : 0,
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
        ];

        $this->languageModel->loadCurrentLanguage();

        if ($this->configModel->save($values)) {
            $this->flash->success(t('Settings saved successfully.'));
        } else {
            $this->flash->failure(t('Unable to save your settings.'));
        }

        return $this->response->redirect($this->helper->url->to('WeekHelperController', 'show', ['plugin' => 'WeekHelper']), true);
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
}