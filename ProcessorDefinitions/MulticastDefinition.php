<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\Multicast;
use JMS\Serializer\Annotation as JMS;

/**
 * Class MulticastDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
class MulticastDefinition extends ProcessorDefinition
{
    const PIPELINE = 'pipeline';
    const TO= 'to';

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        $strategy = @$configNode["strategyRef"]."";

        $this->validateStrategy($strategy);
        $def->addMethodCall('setAggregationStrategy', [$strategy]);

        foreach ($configNode as $nodeName => $nodeValue) {

            switch ($nodeName) {
                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', [(string)$nodeValue]);
                    break;
                default:
                    $itineraryName = $this->getBuilder()->generateNextUniqueReproducibleIdForContext($id);
                    $itinerary = $this->builder->buildItinerary($itineraryName);
                    $this->builder->addNodeToItinerary($itinerary,$nodeName,$nodeValue);
                    $def->addMethodCall('addItinerary', [$itinerary]);
                    break;
            }
        }

        return $def;
    }

    protected function validateStrategy($strategy) {
        if (!in_array($strategy, Multicast::getAvailableAggregationStrategies())) {
            throw new \Exception(
                sprintf(
                    'strategyRef "%s" is not a valid aggregation strategy for multicast, accepted values: %s',
                    $strategy,
                    implode(', ', Multicast::getAvailableAggregationStrategies())
                )
            );
        }
    }
}
