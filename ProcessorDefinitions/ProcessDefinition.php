<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ProcessDefinition.
 */
class ProcessDefinition extends ProcessorDefinition
{
    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);
        $ref = (string) $configNode->attributes()->{'ref'};
        if (!$ref) {
            throw new InvalidConfigurationException('Missing "ref" property for process node.');
        }

        $def->addMethodCall('setProcessor', [new Reference($ref)]);

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $description = (string) $nodeValue;
                    $def->addMethodCall('setDescription', [$description]);
                    break;
                default:
                    throw new InvalidConfigurationException('Unsupported process node: "'.$nodeName.'"');
            }
        }

        return $def;
    }
}
