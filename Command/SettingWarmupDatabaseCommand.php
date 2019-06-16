<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class SettingWarmupDatabaseCommand extends Command
{
    use ContainerAwareTrait;

    protected static $defaultName = 'smart:settings:warmup-database';

    protected function configure()
    {
        $this
            ->setDescription('Warmup database settings.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container->get('settings')->warmupDatabase();
    }
}
