<?php

namespace SmartCore\Bundle\SettingsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartSettingsExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('smart_core.settings.setting_manager', $config['setting_manager']);
        $container->setParameter('smart_core.settings.show_bundle_column', $config['show_bundle_column']);

        $this->createCacheService($container, $config['cache_provider']);

        $alias = new Alias($config['setting_manager']);
        $alias->setPublic(true);

        $container->setAlias('settings', $alias);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $cache_proviver_id
     */
    protected function createCacheService(ContainerBuilder $container, string $cache_proviver_id): void
    {
        $definition = new Definition(
            'SmartCore\\Bundle\\SettingsBundle\\Cache\\CacheProvider', [
                new Reference($cache_proviver_id),
            ]
        );

        $definition->setPublic(true);

        $container->setDefinition('smart_core.settings.cache',$definition);
    }
}
