<?php

namespace SmartCore\Bundle\SettingsBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Yaml\Yaml;

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

        $validator = new SchemaValidator($em);
        if (false === $validator->schemaInSyncWithMetadata()) {
            return;
        }

        $bundles = $container->getParameter('kernel.bundles');

        foreach ($bundles as $_bundleName => $bundleClass) {
            $reflector = new \ReflectionClass($bundleClass);
            $settingsConfig = dirname($reflector->getFileName()).'/Resources/config/settings.yml';

            if (file_exists($settingsConfig)) {
                /** @var Bundle $bundle */
                $bundle = new $bundleClass();

                $settingsConfig = Yaml::parse(file_get_contents($settingsConfig));

                if (!empty($settingsConfig)) {
                    foreach ($settingsConfig as $name => $val) {
                        if (empty($bundle->getContainerExtension())) {
                            continue;
                        }

                        $container->get('settings')->createSetting($bundle->getContainerExtension()->getAlias(), $name, $val);
                    }

                    $em->flush();
                }
            } // _end file_exists($settingsConfig)
        }
    }
}
