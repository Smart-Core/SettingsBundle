<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SettingWarmupDatabaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('smart:settings:warmup-database')
            ->setDescription('Warmup database settings.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('settings')->warmupDatabase();
    }
}
