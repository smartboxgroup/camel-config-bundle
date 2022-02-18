<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\DelayInterceptor;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;

class DelayDefinition extends ProcessorDefinition
{
    use UsesEvaluator;

    /** @var string */
    protected $processorClass = DelayInterceptor::class;

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);
        $delayed = isset($configNode->attributes()->{'delayPeriod'}) ? (int)$configNode->attributes()->{'delayPeriod'} : 0;
        $def->addMethodCall('setDelayPeriod', [$delayed]);

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $description = (string) $nodeValue;
                    $def->addMethodCall('setDescription', [$description]);
                    break;
            }
        }

        return $def;
    }
}
