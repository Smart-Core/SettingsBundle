<?php

namespace SmartCore\Bundle\SettingsBundle\Command;

use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SettingListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('smart:settings:list')
            ->setDescription('List all settings.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new TableStyle();
        $style
            ->setVerticalBorderChars(' ')
            ->setCrossingChar(' ')
        ;

        $table = new Table($output);
        $table
            ->setHeaders(['Hide', 'Name', 'Value'])
            ->setStyle($style)
        ;

        $settings = $this->getContainer()->get('settings')->getSettingsRepo()->findBy([], ['bundle' => 'ASC', 'name' => 'ASC']);

        /** @var SettingModel $setting */
        foreach ($settings as $setting) {
            $table->addRow([
                $setting->isHidden() ? '<comment>yes</comment>' : 'no',
                $setting->getBundle().':'.$setting->getName(),
                $setting->getValueAsString(),
            ]);
        }

        $table->render();

        $output->writeln('Total settings: '.count($settings));
    }
}
