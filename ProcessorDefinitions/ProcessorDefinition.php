<?php

namespace Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\ServiceBusBundle\DependencyInjection\FlowsBuilderInterface;
use Symfony\Component\DependencyInjection\Definition;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ProcessorDefinition
 * @package Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions
 */
abstract class ProcessorDefinition extends Service implements ProcessorDefinitionInterface
{
    const DESCRIPTION = "description";
    const ID = "id";

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
     * @return Definition
     */
    public function getBasicDefinition()
    {
        return $this->builder->getBasicDefinition($this->getProcessorClass());
    }

    public abstract function buildProcessor($configNode);
}
