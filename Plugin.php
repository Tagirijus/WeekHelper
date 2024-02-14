<?php

namespace Kanboard\Plugin\WeekHelper;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Plugin\WeekHelper\Action\TaskAutoAddWeek;
use Kanboard\Plugin\WeekHelper\Model;


class Plugin extends Base
{
    public function initialize()
    {
        // Controller override
        $this->container['subtaskTimeTrackingModel'] = $this->container->factory(function ($container) {
            return new Model\SubtaskTimeTrackingModelMod($container);
        });
        $this->container['taskStatusModel'] = $this->container->factory(function ($container) {
            return new Model\TaskStatusModelMod($container);
        });

        // Automatic Action
        $this->actionManager->register(new TaskAutoAddWeek($this->container));

        // Helper
        $this->helper->register('weekHelperHelper', '\Kanboard\Plugin\WeekHelper\Helper\WeekHelperHelper');
        $this->helper->register('hoursViewHelper', '\Kanboard\Plugin\WeekHelper\Helper\HoursViewHelper');

        // CSS - Asset Hook
        $this->hook->on('template:layout:css', array('template' => 'plugins/WeekHelper/Assets/css/week-helper.min.css'));

        // JS - Asset Hook
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/jquery.textcomplete.min.js'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/weekhelper-functions.min.js'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/week-replacer.min.js'));
        if ($this->configModel->get('weekhelper_headerdate_enabled', 1) == 1) {
            $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/add-date-to-header.min.js'));
        }
        if ($this->configModel->get('weekhelper_time_box_enabled', 1) == 1) {
            $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/add-date-to-sticky.min.js'));
        }
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/checkbox-inserter.min.js'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/subtask-toggle-refresh.min.js'));

        // Views - Template Hook
        $this->template->hook->attach(
            'template:config:sidebar', 'WeekHelper:config/weekhelper_configWeeks_sidebar');
        $this->template->hook->attach(
            'template:config:sidebar', 'WeekHelper:config/weekhelper_configHoursView_sidebar');
        $this->template->hook->attach(
            'template:config:sidebar', 'WeekHelper:config/weekhelper_configRemainingBox_sidebar');
        if ($this->configModel->get('weekhelper_time_box_enabled', 1) == 1) {
            $this->template->hook->attach(
                'template:layout:bottom', 'WeekHelper:time_box');
        }
        $this->template->hook->attach(
            'template:project:header:before', 'WeekHelper:board/project_head_hours', [
                'tagiTimes' => function ($projectId) {
                    return $this->helper->hoursViewHelper->getTimesByProjectId($projectId);
                }
            ]
        );
        $this->template->hook->attach(
            'template:board:column:header', 'WeekHelper:board/column_hours', [
                'tagiTimes' => function ($column) {
                    return $this->helper->hoursViewHelper->getTimesForColumn($column);
                }
            ]
        );
        $this->template->hook->attach(
            'template:dashboard:show:after-filter-box', 'WeekHelper:dashboard/project_times_summary_all', [
                'tagiTimes' => function ($userId) {
                    return $this->helper->hoursViewHelper->getTimesByUserId($userId);
                }
            ]
        );
        $this->template->hook->attach(
            'template:dashboard:project:after-title', 'WeekHelper:dashboard/project_times_summary_single', [
                'tagiTimes' => function ($projectId) {
                    return $this->helper->hoursViewHelper->getTimesByProjectId($projectId);
                }
            ]
        );

        // Template Overrides
        $this->template->setTemplateOverride('board/task_public', 'WeekHelper:board/task_public');
        $this->template->setTemplateOverride('board/task_private', 'WeekHelper:board/task_private');
        $this->template->setTemplateOverride('task_list/task_title', 'WeekHelper:task_list/task_title');
        $this->template->setTemplateOverride('search/results', 'WeekHelper:search/results');
        $this->template->setTemplateOverride('task/details', 'WeekHelper:task/details');
        $this->template->setTemplateOverride('board/task_footer', 'WeekHelper:board/task_footer');
        $this->template->setTemplateOverride('task_list/task_icons', 'WeekHelper:task_list/task_icons');
        $this->template->setTemplateOverride('subtask/timer', 'WeekHelper:subtask/timer');
        $this->template->setTemplateOverride('task_internal_link/table', 'WeekHelper:task_internal_link/table');

        // Extra Page - Routes
        $this->route->addRoute('/weekhelper/configWeeks', 'WeekHelperController', 'showConfigWeeks', 'WeekHelper');
        $this->route->addRoute('/weekhelper/configHoursView', 'WeekHelperController', 'showConfigHoursView', 'WeekHelper');
        $this->route->addRoute('/weekhelper/configRemainingBox', 'WeekHelperController', 'showConfigRemainingBox', 'WeekHelper');
        $this->route->addRoute('/weekhelper/weekpattern', 'WeekHelperController', 'getWeekPattern', 'WeekHelper');
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
        return '2.6.0';
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
