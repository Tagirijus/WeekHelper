<?php

namespace Kanboard\Plugin\WeekHelper;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Plugin\WeekHelper\Action\TaskAutoAddWeek;
use Kanboard\Plugin\WeekHelper\Action\TaskAutoAddWeekOnCreate;
use Kanboard\Plugin\WeekHelper\Model;
use Kanboard\Plugin\WeekHelper\Helper\AutomaticPlanner;
use Kanboard\Plugin\WeekHelper\Console\UpdateBlockingTasks;



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
        $this->actionManager->register(new TaskAutoAddWeekOnCreate($this->container));

        // Helper
        $this->helper->register('weekHelperHelper', '\Kanboard\Plugin\WeekHelper\Helper\WeekHelperHelper');
        $this->helper->register('hoursViewHelper', '\Kanboard\Plugin\WeekHelper\Helper\HoursViewHelper');
        $this->helper->register('automaticPlanner', '\Kanboard\Plugin\WeekHelper\Helper\AutomaticPlanner');

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
        $this->template->hook->attach(
            'template:config:sidebar', 'WeekHelper:config/weekhelper_configAutomaticPlanner_sidebar');
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
        // levels on dashboard sidebar
        if (
            $this->configModel->get('hoursview_dashboard_link_level_1', 0)
        ) {
            $this->template->hook->attach(
                'template:dashboard:sidebar', 'WeekHelper:dashboard/level_on_dashboard_sidebar', [
                    'level' => 'level_1',
                    'caption' => $this->configModel->get('hoursview_level_1_caption', 'level_1')
                ]
            );
        }
        if (
            $this->configModel->get('hoursview_dashboard_link_level_2', 0)
        ) {
            $this->template->hook->attach(
                'template:dashboard:sidebar', 'WeekHelper:dashboard/level_on_dashboard_sidebar', [
                    'level' => 'level_2',
                    'caption' => $this->configModel->get('hoursview_level_2_caption', 'level_2')
                ]
            );
        }
        if (
            $this->configModel->get('hoursview_dashboard_link_level_3', 0)
        ) {
            $this->template->hook->attach(
                'template:dashboard:sidebar', 'WeekHelper:dashboard/level_on_dashboard_sidebar', [
                    'level' => 'level_3',
                    'caption' => $this->configModel->get('hoursview_level_3_caption', 'level_3')
                ]
            );
        }
        if (
            $this->configModel->get('hoursview_dashboard_link_level_4', 0)
        ) {
            $this->template->hook->attach(
                'template:dashboard:sidebar', 'WeekHelper:dashboard/level_on_dashboard_sidebar', [
                    'level' => 'level_4',
                    'caption' => $this->configModel->get('hoursview_level_4_caption', 'level_4')
                ]
            );
        }
        if (
            $this->configModel->get('hoursview_dashboard_link_level_all', 0)
        ) {
            $this->template->hook->attach(
                'template:dashboard:sidebar', 'WeekHelper:dashboard/level_on_dashboard_sidebar', [
                    'level' => 'all',
                    'caption' => $this->configModel->get('hoursview_all_caption', 'All')
                ]
            );
        }
        // info about non-time-mode
        $non_time_mode_minutes = $this->configModel->get('hoursview_non_time_mode_minutes', 0);
        if ($non_time_mode_minutes != 0) {
            $this->template->hook->attach(
                'template:task:form:first-column',
                'WeekHelper:task_creation_modal/complexity_explanation',
                ['non_time_mode_minutes' => $non_time_mode_minutes]
            );
        }

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
        $this->route->addRoute('/weekhelper/configAutomaticPlanner', 'WeekHelperController', 'showConfigAutomaticPlanner', 'WeekHelper');
        $this->route->addRoute('/weekhelper/weekpattern', 'WeekHelperController', 'getWeekPattern', 'WeekHelper');
        $this->route->addRoute('/weekhelper/dashboard_level/:level', 'WeekHelperController', 'showLevelHoverAsPage', 'WeekHelper');
        $this->route->addRoute('/weekhelper/automaticplan', 'WeekHelperController', 'getAutomaticPlan', 'WeekHelper');
        $this->route->addRoute('/weekhelper/updateblockingtasks', 'WeekHelperController', 'updateBlockingTasks', 'WeekHelper');

        // JSONRPC - Methods
        $this->container['automaticPlanner'] = function ($c) {
            return new AutomaticPlanner($c);
        };
        $container = $this->container;
        $this->api->getProcedureHandler()->withCallback(
            'automaticPlanner.getAutomaticPlanAsText',
            function(
                $params
            ) use ($container) {
                return [
                    'ok' => true,
                    'received' => $container['automaticPlanner']->getAutomaticPlanAsText(
                        $params
                    ),
                ];
            }
        );
        $this->api->getProcedureHandler()->withCallback(
            'automaticPlanner.getAutomaticPlanAsJSON',
            function($params) use ($container) {
                return [
                    'ok' => true,
                    'received' => $container['automaticPlanner']->getAutomaticPlanAsArray($params),
                ];
            }
        );

        // CLI Commands
        $this->cli->add(new UpdateBlockingTasks($this->helper->automaticPlanner));
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
        return '2.16.2';
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
