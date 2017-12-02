<?php

namespace SmartCore\Bundle\SettingsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class SettingListAliasCommand extends SettingListCommand implements ContainerAwareInterface
{
    protected function configure()
    {
        $this
            ->setName('debug:settings')
            ->setDescription('Alias for List all settings.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
    }
}
