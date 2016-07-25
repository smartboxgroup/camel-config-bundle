<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Core\Processors\ProcessorInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ProcessDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
class ProcessDefinition extends ProcessorDefinition
{
    protected $processorsRegistry;

    public function setProcessorsRegistry($processorsRegistry)
    {
        $this->processorsRegistry = $processorsRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);
        if (!isset($configNode['ref'])) {
            throw new InvalidConfigurationException('Missing "ref" property for process node.');
        }

//        $processor = $this->resolveProcessor($configNode['ref']);
        $def->addMethodCall('setProcessor', [new Reference($configNode['ref'])]);

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $description = (string)$nodeValue;
                    $def->addMethodCall('setDescription', [$description]);
                    break;
                default:
                    throw new InvalidConfigurationException('Unsupported process node: "' . $nodeName . '"');
            }
        }

        return $def;
    }

//    /**
//     * @param $processorId
//     * @return ProcessorInterface
//     * @throws InvalidConfigurationException
//     */
//    public function resolveProcessor($processorId)
//    {
//        /** @var ProcessorInterface $processor */
//        $processor = $this->processorsRegistry->find($processorId);
//        if (is_null($processor)) {
//            throw new InvalidConfigurationException(
//                sprintf(
//                    'Could not find processor with ref "%s" for process node.',
//                    $processorId
//                )
//            );
//        }
//
//        if (!$processor instanceof ProcessorInterface) {
//            throw new InvalidConfigurationException(
//                sprintf(
//                    'Processor with ref "%s" for process node should implement "%s" interface.',
//                    $processorId,
//                    ProcessorInterface::class
//                )
//            );
//        }
//
//        return $processor;
//    }
}