<?php

namespace Kanboard\Plugin\WeekHelper;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Plugin\WeekHelper\Action\TaskAutoAddWeek;


class Plugin extends Base
{
    public function initialize()
    {
        // Automatic Action
        $this->actionManager->register(new TaskAutoAddWeek($this->container));

        // Helper
        $this->helper->register('weekHelperHelper', '\Kanboard\Plugin\WeekHelper\Helper\WeekHelperHelper');

        // CSS - Asset Hook
        $this->hook->on('template:layout:css', array('template' => 'plugins/WeekHelper/Assets/css/week-helper.min.css'));

        // JS - Asset Hook
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/jquery.textcomplete.min.js'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/weekhelper-functions.min.js'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/week-replacer.min.js'));
        if ($this->configModel->get('weekhelper_headerdate_enabled', 1) == 1) {
            $this->hook->on('template:layout:js', array('template' => 'plugins/WeekHelper/Assets/js/add-date-to-header.min.js'));
        }

        // Views - Template Hook
        $this->template->hook->attach(
            'template:config:sidebar', 'WeekHelper:config/weekhelper_config_sidebar');

        // Template Overrides
        $this->template->setTemplateOverride('board/task_public', 'WeekHelper:board/task_public');
        $this->template->setTemplateOverride('board/task_private', 'WeekHelper:board/task_private');
        $this->template->setTemplateOverride('task_list/task_title', 'WeekHelper:task_list/task_title');

        // Extra Page - Routes
        $this->route->addRoute('/weekhelper/config', 'WeekHelperController', 'show', 'WeekHelper');
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
