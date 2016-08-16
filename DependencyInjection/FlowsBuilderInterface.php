<?php

namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Interface FlowsBuilderInterface
 * @package Smartbox\Integration\CamelConfigBundle\DependencyInjection
 */
interface FlowsBuilderInterface
{

    /**
     * @param string $name
     * @param \Traversable $config
     * @return Definition
     */
    public function buildProcessor($name, $config);

    /**
     * @param \Traversable $config
     * @param string $flowName
     */
    public function buildFlow($config, $flowName = null);

    /**
     * @param Definition|Reference $itinerary
     * @param $nodeName
     * @param $nodeConfig
     */
    public function addNodeToItinerary(Reference $itinerary, $nodeName, $nodeConfig);

    /**
     * @param Reference $itinerary
     * @param Definition $processor
     * @param string|null $id
     * @return mixed
     */
    public function addProcessorDefinitionToItinerary(Reference $itinerary, Definition $processor, $id = null);

    /**
     * @param Definition|Reference $itinerary
     * @param Reference $processor
     */
    public function addToItinerary(Reference $itinerary, Reference $processor);

    /**
     * @param $config
     * @return Reference
     */
    public function buildEndpoint($config);

    /**
     * Important: The name of the itinerary must be reproducible on every compiler pass and unique in the container.
     * It is recommended to use some id of the context where is used to build it.
     *
     * @param $name string Must be unique and must be reproducible on every compiler pass when the flows don't change
     * @return Reference
     */
    public function buildItinerary($name);

    /**
     * @param Definition $definition
     * @param string $name
     * @return Reference
     */
    public function registerItinerary(Definition $definition, $name);

    /**
     * @return Definition
     */
    public function getBasicDefinition($class);

    /**
     * @param string $name
     * @return string
     */
    public function getParameter($name);

    /**
     * @param $contextId
     * @return string
     */
    public function generateNextUniqueReproducibleIdForContext($contextId);
}