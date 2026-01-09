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
        $output->writeln('WeekHelper will update blocking tasks from calendar ...');
        $success = $this->automaticPlanner->updateBlockingTasks();
        if ($success === true) {
            $output->writeln('Successfully updated blocking tasks from CalDAV!');
        } else {
            $output->writeln('Fail. Message:');
            $output->writeln($success);
        }

        return 0;
    }
}
