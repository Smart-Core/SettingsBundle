<?php

namespace SmartCore\Bundle\SettingsBundle;

use SmartCore\Bundle\SettingsBundle\DependencyInjection\Compiler\RegisterSettingsManagerPass;
use SmartCore\Bundle\SettingsBundle\DependencyInjection\Compiler\SettingsPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SmartSettingsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        //$container->addCompilerPass(new RegisterSettingsManagerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new SettingsPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
