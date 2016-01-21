<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Processors\Routing\Multicast;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class StopDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
class StopDefinition extends ProcessorDefinition
{
    const STOP = 'stop';

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', [(string)$nodeValue]);
                    break;
                default:
                    throw new InvalidConfigurationException('Unsupported stop processor node: "' . $nodeName . '"');
                    break;
            }
        }

        return $def;
    }
}
