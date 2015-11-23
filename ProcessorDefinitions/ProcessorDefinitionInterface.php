<?php
namespace Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions;

use Smartbox\Integration\ServiceBusBundle\DependencyInjection\FlowsBuilderInterface;
use Symfony\Component\DependencyInjection\Reference;


/**
 * Class ProcessorDefinition
 * @package Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions
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