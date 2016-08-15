<?php

namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('smartbox_integration_camel_config');
        $rootNode
            ->children()
                ->arrayNode('flows_directories')
                    ->prototype('scalar')
                        ->validate()
                            ->ifTrue(
                                function ($folder) {
                                    return !file_exists($folder) || !is_dir($folder);
                                }
                            )
                            ->thenInvalid('"%s" is not an existent directory.')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('frozen_flows_directory')->isRequired()->cannotBeEmpty()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
