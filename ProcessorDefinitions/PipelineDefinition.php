<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Symfony\Component\DependencyInjection\Reference;
use JMS\Serializer\Annotation as JMS;

/**
 * Class PipelineDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
class PipelineDefinition extends ProcessorDefinition
{
    const PIPELINE = 'pipeline';

    /**
     * @param $configNode array
     * @return Reference
     * @throws \Exception
     */
    public function buildProcessor($configNode)
    {
        $def = parent::buildProcessor($configNode);

        $itinerary = $this->builder->buildItinerary();
        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', [(string)$nodeValue]);
                    break;
                default:
                    $this->builder->addNodeToItinerary($itinerary,$nodeName,$nodeValue);
                    break;
            }
        }
        $def->addMethodCall('setItinerary', [$itinerary]);

        $reference = $this->builder->registerService($def, self::PIPELINE);

        return $reference;
    }
}
