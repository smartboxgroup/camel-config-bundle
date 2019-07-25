<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Class AppKernel.
 */
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Smartbox\CoreBundle\SmartboxCoreBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Smartbox\Integration\FrameworkBundle\SmartboxIntegrationFrameworkBundle(),
            new \Smartbox\Integration\CamelConfigBundle\SmartboxIntegrationCamelConfigBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
}
