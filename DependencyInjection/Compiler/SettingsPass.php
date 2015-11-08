<?php

namespace SmartCore\Bundle\SettingsBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Tools\SchemaValidator;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
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
                        $setting = new Setting();
                        $setting
                            ->setBundle($bundle->getContainerExtension()->getAlias())
                            ->setName($name)
                            ->setValue($val)
                            ->setType(is_bool($val) ? Setting::TYPE_BOOL : Setting::TYPE_TEXT)
                        ;

                        $errors = $container->get('validator')->validate($setting);

                        if (count($errors) > 0) {
                            $em->detach($setting);
                        } else {
                            $em->persist($setting);
                        }
                    }

                    $em->flush();
                }
            } // _end file_exists($settingsConfig)
        }
    }
}
