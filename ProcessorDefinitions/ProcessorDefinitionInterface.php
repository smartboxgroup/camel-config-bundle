<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ProcessorDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
interface ProcessorDefinitionInterface
{
    /**
     * @param FlowsBuilderInterface $builder
     */
    public function setBuilder(FlowsBuilderInterface $builder);

    /**
     * @return mixed
     */
    public function getProcessorClass();

    /**
     * @param $configNode array
     * @return Reference
     */
    public function buildProcessor($configNode);
}