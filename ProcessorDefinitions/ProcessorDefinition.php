<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class ProcessorDefinition.
 */
abstract class ProcessorDefinition extends Service implements ProcessorDefinitionInterface
{
    const DESCRIPTION = 'description';
    const ID = 'id';
    const ATTRIBUTE_RUNTIME_BREAKPOINT = 'runtime-breakpoint';
    const ATTRIBUTE_COMPILETIME_BREAKPOINT = 'compiletime-breakpoint';

    /** @var string */
    protected $processorClass;

    /** @var FlowsBuilderInterface */
    protected $builder;

    protected $debug = false;

    /**
     * @return FlowsBuilderInterface
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /** {@inheritdoc} */
    public function setBuilder(FlowsBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /** {@inheritdoc} */
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
     * {@inheritdoc}
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $definition = $this->getBasicDefinition();

        if ($configNode instanceof \SimpleXMLElement) {
            $attributes = $configNode->attributes();

            // runtime debug breakpoint
            if (
                isset($attributes[self::ATTRIBUTE_RUNTIME_BREAKPOINT]) &&
                $attributes[self::ATTRIBUTE_RUNTIME_BREAKPOINT] == true &&
                $this->debug
            ) {
                $definition->addMethodCall('setRuntimeBreakpoint', [true]);
            }

            // compile time debug breakpoint
            if (
                isset($attributes[self::ATTRIBUTE_COMPILETIME_BREAKPOINT]) &&
                $attributes[self::ATTRIBUTE_COMPILETIME_BREAKPOINT] == true &&
                $this->debug
            ) {
                if (function_exists('xdebug_break')) {
                    xdebug_break();
                }
            }
        }

        /*
         *
         * DEBUGGING HINTS
         *
         * In case you are adding a compile time breakpoint in a flow xml xdebug will stop here.
         *
         * When you step out from this function you will get into the function you want to debug.
         *
         * The definition of the processor you are debugging is extending this method.
         *
         * To debug in that way you can add this to your xml flow file, as part of the processor you want to debug:
         *
         *      <... compiletime-breakpoint="1"/>
         *
         */

        return $definition;
    }

    /**
     * @return Definition
     */
    protected function getBasicDefinition()
    {
        return $this->builder->getBasicDefinition($this->getProcessorClass());
    }
}
