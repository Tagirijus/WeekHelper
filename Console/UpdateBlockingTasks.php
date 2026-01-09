<?php

namespace Kanboard\Plugin\WeekHelper\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBlockingTasks extends Command
{
    protected static $defaultName = 'weekhelper:update-blocking-tasks';
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Run Weekhelper blocking tasks update, which will fetch blocking tasks from calendar.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('WeekHelper will update blocking tasks from calendar ...');
        $output->writeln('THIS METHOD IS WIP');

        return 0;
    }
}
