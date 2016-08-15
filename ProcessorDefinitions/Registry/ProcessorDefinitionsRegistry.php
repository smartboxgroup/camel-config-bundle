<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\Registry;

use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\ProcessorDefinitionInterface;

/**
 * Class ProcessorDefinitionsRegistry.
 */
class ProcessorDefinitionsRegistry
{
    /** @var array */
    protected $items = [];

    /**
     * @param $nodeName
     * @param ProcessorDefinitionInterface $definition
     *
     * @throws \RuntimeException
     */
    public function register($nodeName, ProcessorDefinitionInterface $definition)
    {
        if (array_key_exists($nodeName, $this->items)) {
            throw new \RuntimeException(sprintf('Processor definition for node name "%s" already exists in registry.', $nodeName));
        }

        $this->items[$nodeName] = $definition;
    }

    /**
     * @param $nodeName
     *
     * @return ProcessorDefinitionInterface
     *
     * @throws \RuntimeException
     */
    public function findDefinition($nodeName)
    {
        if (!array_key_exists($nodeName, $this->items)) {
            throw new \RuntimeException('Can not find processor definition for node name: '.$nodeName);
        }

        return $this->items[$nodeName];
    }

    /**
     * @return array
     */
    public function getRegisteredDefinitionsNodeNames()
    {
        return array_keys($this->items);
    }
}
