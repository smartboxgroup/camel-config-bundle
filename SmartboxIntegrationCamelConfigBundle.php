<?php

namespace Smartbox\Integration\CamelConfigBundle;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class SmartboxIntegrationCamelConfigBundle
 * @package Smartbox\Integration\CamelConfigBundle
 */
class SmartboxIntegrationCamelConfigBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FlowsBuilderCompilerPass());
    }
}
