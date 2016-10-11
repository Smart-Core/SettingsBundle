<?php

namespace SmartCore\Bundle\SettingsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterSettingsManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        //$settingsManagerService = $container->getDefinition($container->getParameter('smart_core.settings.setting_manager'));

//        $container->setAlias('settings', new Alias($container->getParameter('smart_core.settings.setting_manager')));
    }
}
