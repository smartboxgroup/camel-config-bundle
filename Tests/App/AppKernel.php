<?php

namespace Smartbox\Integration\ServiceBusBundle\Tests\App;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \Smartbox\CoreBundle\SmartboxCoreBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Smartbox\Integration\FrameworkBundle\SmartboxIntegrationFrameworkBundle(),
            new \Smartbox\Integration\ServiceBusBundle\SmartboxIntegrationServiceBusBundle()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
}