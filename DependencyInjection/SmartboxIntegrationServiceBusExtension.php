<?php

namespace Smartbox\Integration\ServiceBusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationServiceBusExtension extends Extension
{
    protected $config;

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getFlowsDirectories()
    {
        return $this->config['flows_directories'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $eventsLogLevel = $this->config['events_log_level'];
        $container->setParameter('smartesb.event_listener.events_logger.log_level', $eventsLogLevel);

        $container->setParameter('smartesb.flows_directories', $this->config['flows_directories']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('exceptions.yml');
        $loader->load('connectors.yml');
    }
}
