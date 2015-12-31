<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderInterface;
use Symfony\Component\DependencyInjection\Definition;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ProcessorDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
abstract class ProcessorDefinition extends Service implements ProcessorDefinitionInterface
{
    const DESCRIPTION = "description";
    const ID = "id";
    const ATTRIBUTE_RUNTIME_BREAKPOINT = "runtime-breakpoint";
    const ATTRIBUTE_COMPILETIME_BREAKPOINT = "compiletime-breakpoint";

    /** @var  string */
    protected $processorClass;

    /** @var  FlowsBuilderInterface */
    protected $builder;

    /**
     * @return FlowsBuilderInterface
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /** {@inheritDoc} */
    public function setBuilder(FlowsBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /** {@inheritDoc} */
    public function getProcessorClass()
    {
        return $this->processorClass;
    }

    /**
     * @param mixed $processorClass
     */
    public function setProcessorClass($processorClass)
    {
        $this->processorClass = $processorClass;
    }

    /**
     * {@inheritDoc}
     */
    public function buildProcessor($configNode)
    {
        $definition = $this->getBasicDefinition();

        if ($configNode instanceof \SimpleXMLElement) {
            $attributes = $configNode->attributes();

            // compile time debug breakpoint
            if (
                isset($attributes[self::ATTRIBUTE_COMPILETIME_BREAKPOINT]) &&
                $attributes[self::ATTRIBUTE_COMPILETIME_BREAKPOINT] == true
            ) {
                if (function_exists('xdebug_break')) {
                    xdebug_break();
                }
            }

            // runtime debug breakpoint
            if (
                isset($attributes[self::ATTRIBUTE_RUNTIME_BREAKPOINT]) &&
                $attributes[self::ATTRIBUTE_RUNTIME_BREAKPOINT] == true
            ) {
                $definition->addMethodCall('setRuntimeBreakpoint', [true]);
            }
        }

        return $definition;
    }

    /**
     * @return Definition
     */
    private function getBasicDefinition()
    {
        return $this->builder->getBasicDefinition($this->getProcessorClass());
    }
}
