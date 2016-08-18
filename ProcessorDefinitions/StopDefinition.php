<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class StopDefinition.
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
                    $def->addMethodCall('setDescription', [(string) $nodeValue]);
                    break;
                default:
                    throw new InvalidConfigurationException('Unsupported stop processor node: "'.$nodeName.'"');
                    break;
            }
        }

        return $def;
    }
}
