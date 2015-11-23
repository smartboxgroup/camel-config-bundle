<?php

namespace Smartbox\Integration\ServiceBusBundle\DependencyInjection;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
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
        $rootNode = $treeBuilder->root('smartbox_integration_service_bus');
        $rootNode
            ->children()
                ->scalarNode('events_log_level')
                    ->defaultValue(LogLevel::DEBUG)
                    ->validate()
                        ->ifNotInArray([
                            LogLevel::EMERGENCY,
                            LogLevel::ALERT,
                            LogLevel::CRITICAL,
                            LogLevel::ERROR,
                            LogLevel::WARNING,
                            LogLevel::NOTICE,
                            LogLevel::INFO,
                            LogLevel::DEBUG,
                        ])
                        ->thenInvalid('Invalid log level for events log: "%s"')
                    ->end()
                ->end()
                ->arrayNode('flows_directories')
                    ->prototype('scalar')
                        ->validate()
                            ->ifTrue(
                                function ($folder) {
                                    return (!file_exists($folder) || !is_dir($folder));
                                }
                            )
                            ->thenInvalid('"%s" is not an existent directory.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
