<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SettingsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        try {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $container->get('doctrine.orm.entity_manager');
        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            if ($container->getParameter('kernel.debug')) {
                echo __CLASS__.': Unavailable DB connection. Please fix it and rebuild cache.';
            }

            return;
        }

        $container->get('settings')->warmupDatabase();
    }
}
