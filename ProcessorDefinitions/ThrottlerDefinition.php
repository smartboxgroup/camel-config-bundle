<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\Throttler;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Symfony\Component\Form\Exception\InvalidConfigurationException;

class ThrottlerDefinition extends ProcessorDefinition
{
    const SIMPLE = 'simple';

    use UsesEvaluator;

    /** @var  string */
    protected $processorClass = Throttler::class;

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        // timePeriodMillis
        $timeMs = (int) $configNode->attributes()->{'timePeriodMillis'};
        if (!$timeMs && is_int($timeMs) && !$timeMs >= 0) {
            throw new \RuntimeException('The attribute timePeriodMillis of the throttler processor must be defined and be an integer >= 0');
        }

        // asyncDelayed
        $asyncDelayed = false;
        if (isset($configNode->attributes()->{'asyncDelayed'})) {
            $asyncDelayed = strtolower($configNode->attributes()->{'asyncDelayed'});
            if (!in_array($asyncDelayed, ['true', 'false'])) {
                throw new \RuntimeException(sprintf(
                    'The attribute asyncDelayed must be boolean ("true" or "false"): "%s" given',
                    $asyncDelayed
                ));
            }
            $asyncDelayed = $asyncDelayed === 'true' ? true : false;
        }

        $def->addMethodCall('setPeriodMs', [$timeMs]);
        $def->addMethodCall('setAsyncDelayed', [$asyncDelayed]);

        $expression = null;

        $itineraryName = $this->getBuilder()->generateNextUniqueReproducibleIdForContext($id);
        $itineraryRef = $this->builder->buildItinerary($itineraryName);
        $evaluator = $this->getEvaluator();

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::SIMPLE:
                    $expression = (string) $nodeValue;
                    try {
                        $evaluator->compile($expression, $this->evaluator->getExchangeExposedVars());
                    } catch (\Exception $e) {
                        throw new InvalidConfigurationException(
                            "Given value ({$expression}) should be a valid expression: ".$e->getMessage(),
                            $e->getCode(),
                            $e
                        );
                    }
                    break;

                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', (string) $nodeValue);
                    break;

                default:
                    $this->builder->addNodeToItinerary($itineraryRef, $nodeName, $nodeValue);
                    break;
            }
        }

        if ($expression === null) {
            throw new \RuntimeException('An expression must be defined for the throttler processor');
        }

        $def->addMethodCall('setItinerary', [$itineraryRef]);
        $def->addMethodCall('setLimitExpression', [$expression]);

        return $def;
    }
}
