<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class ProcessorDefinition.
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
     * @param $configNode
     * @param string $id
     *
     * @return Definition
     */
    public function buildProcessor($configNode, $id);

    /**
     * @param bool $debug
     */
    public function setDebug($debug);
}
