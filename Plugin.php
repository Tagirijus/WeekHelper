<?php

namespace Kanboard\Plugin\WeekHelper;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;


class Plugin extends Base
{
    public function initialize()
    {

        // Helper
        $this->helper->register('weekHelperHelper', '\Kanboard\Plugin\WeekHelper\Helper\WeekHelperHelper');

        // CSS - Asset Hook
        $this->hook->on('template:layout:css', array('template' => 'plugins/WeekHelper/Assets/css/week-helper.min.css'));

        // JS - Asset Hook
        if ($this->configModel->get('weekhelper_headerdate_enabled', 1) == 1) {
            $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/add-date-to-header.min.js'));
        }

        // Template Override
        // $this->template->setTemplateOverride('search/results', 'WeekHelper:search/results');
        // $this->template->setTemplateOverride('task/details', 'WeekHelper:task/details');
        // $this->template->setTemplateOverride('board/task_footer', 'WeekHelper:board/task_footer');
        // $this->template->setTemplateOverride('task_list/task_icons', 'WeekHelper:task_list/task_icons');
        // $this->template->setTemplateOverride('subtask/timer', 'WeekHelper:subtask/timer');
        // $this->template->setTemplateOverride('task_internal_link/table', 'WeekHelper:task_internal_link/table');

        // Views - Template Hook
        // $this->template->hook->attach(
        //     'template:project:header:before', 'WeekHelper:board/project_head_hours', [
        //         'tagiTimes' => function ($projectId) {
        //             return $this->helper->weekHelperHelper->getTimesByProjectId($projectId);
        //         }
        //     ]
        // );
        // $this->template->hook->attach(
        //     'template:board:column:header', 'WeekHelper:board/column_hours', [
        //         'tagiTimes' => function ($column) {
        //             return $this->helper->weekHelperHelper->getTimesForColumn($column);
        //         }
        //     ]
        // );
        // $this->template->hook->attach(
        //     'template:dashboard:show:after-filter-box', 'WeekHelper:dashboard/project_times_summary_all', [
        //         'tagiTimes' => function ($userId) {
        //             return $this->helper->weekHelperHelper->getTimesByUserId($userId);
        //         }
        //     ]
        // );
        // $this->template->hook->attach(
        //     'template:dashboard:project:after-title', 'WeekHelper:dashboard/project_times_summary_single', [
        //         'tagiTimes' => function ($projectId) {
        //             return $this->helper->weekHelperHelper->getTimesByProjectId($projectId);
        //         }
        //     ]
        // );
        $this->template->hook->attach(
            'template:config:sidebar', 'WeekHelper:config/weekhelper_config_sidebar');

        // Extra Page - Routes
        $this->route->addRoute('/weekhelper/config', 'WeekHelperController', 'show', 'WeekHelper');
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginName()
    {
        return 'WeekHelper';
    }

    public function getPluginDescription()
    {
        return t('Add little helper for better plan the week ahead');
    }

    public function getPluginAuthor()
    {
        return 'Tagirijus';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getCompatibleVersion()
    {
        // Examples:
        // >=1.0.37
        // <1.0.37
        // <=1.0.37
        return '>=1.2.27';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/Tagirijus/WeekHelper';
    }
}
