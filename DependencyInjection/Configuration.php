<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('smart_core_settings');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('smart_core_settings');
        }

        $rootNode
            ->children()
                ->scalarNode('cache_provider')
                    ->defaultValue('smart_settings_cache_pool.filesystem')
                ->end()
                ->booleanNode('show_bundle_column')
                    ->defaultTrue()
                ->end()
                ->scalarNode('setting_manager')
                    ->defaultValue('smart_core.settings.manager')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) { return strtolower($v); })
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
