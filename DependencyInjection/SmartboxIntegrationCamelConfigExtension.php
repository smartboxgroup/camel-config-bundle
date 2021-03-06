<?php

namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationCamelConfigExtension extends Extension
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

    public function getFrozenFlowsDirectory()
    {
        return $this->config['frozen_flows_directory'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('smartesb.flows_directories', $this->getFlowsDirectories());
        $container->setParameter('smartesb.frozen_flows_directory', $this->getFrozenFlowsDirectory());

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
