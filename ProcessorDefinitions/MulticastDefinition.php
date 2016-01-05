<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Processors\Routing\Multicast;
use Symfony\Component\DependencyInjection\Reference;
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
     * @param $configNode array
     * @return Reference
     * @throws \Exception
     */
    public function buildProcessor($configNode)
    {
        $def = parent::buildProcessor($configNode);

        $strategy = @$configNode["strategyRef"]."";

        $this->validateStrategy($strategy);
        $def->addMethodCall('setAggregationStrategy', [$strategy]);


        foreach ($configNode as $nodeName => $nodeValue) {

            switch ($nodeName) {
                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', [(string)$nodeValue]);
                    break;
                default:
                    $itinerary = $this->builder->buildItinerary();
                    $this->builder->addNodeToItinerary($itinerary,$nodeName,$nodeValue);
                    $def->addMethodCall('addItinerary', [$itinerary]);
                    break;
            }
        }

        $reference = $this->builder->registerService($def, 'multicast');

        return $reference;
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
