<?php

namespace Kanboard\Plugin\WeekHelper\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBlockingTasks extends Command
{
    protected static $defaultName = 'weekhelper:update-blocking-tasks';
    protected $automaticPlanner;

    public function __construct($automaticPlanner)
    {
        $this->automaticPlanner = $automaticPlanner;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Run Weekhelper blocking tasks update, which will fetch blocking tasks from calendar.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $success = $this->automaticPlanner->updateBlockingTasks();
        if ($success === false) {
            $output->writeln(date('Y-m-d H:i:s') . ': Fail. See debug.log for more info.');
        }

        return 0;
    }
}
