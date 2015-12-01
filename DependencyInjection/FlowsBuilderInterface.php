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
     * @param string $name
     */
    public function buildFlow($config, $name = null);

    /**
     * @param Definition|Reference $itinerary
     * @param $configNode
     */
    public function addNodeToItinerary(Reference $itinerary, $nodeName, $nodeConfig);

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
     * @return Reference
     */
    public function buildItinerary();

    /**
     * @param Definition $definition
     * @param string $prefix
     * @return Reference
     */
    public function registerService(Definition $definition, $prefix);

    /**
     * @return Definition
     */
    public function getBasicDefinition($class);

    /**
     * @param string $name
     * @return string
     */
    public function getParameter($name);
}