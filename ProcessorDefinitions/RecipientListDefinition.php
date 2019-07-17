<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class RecipientListDefinition extends ProcessorDefinition
{
    use UsesEvaluator;

    const AGGREGATION_STRATEGY_FIRE_AND_FORGET = 'fireAndForget';
    const SIMPLE = 'simple';
    const DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $definition = parent::buildProcessor($configNode, $id);

        $evaluator = $this->getEvaluator();

        $delimiter = (string) $configNode->attributes()->{'delimiter'};
        if (empty($delimiter)) {
            $delimiter = self::DELIMITER;
        }

        $strategy = (string) $configNode->attributes()->{'strategyRef'};
        $this->validateStrategy($strategy);

        $definition->addMethodCall('setDelimiter', [$delimiter]);
        $definition->addMethodCall('setAggregationStrategy', [$strategy]);

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::SIMPLE:
                    $expression = (string) $nodeValue;
                    try {
                        $evaluator->compile($expression, $evaluator->getExchangeExposedVars());
                    } catch (\Exception $e) {
                        throw new InvalidConfigurationException(
                            "Given value ($expression) should be a valid expression: ".$e->getMessage(),
                            $e->getCode(),
                            $e
                        );
                    }
                    $definition->addMethodCall('setExpression', [$expression]);
                    break;

                case self::DESCRIPTION:
                    $definition->addMethodCall('setDescription', [(string) $nodeValue]);
                    break;
            }
        }

        return $definition;
    }

    /**
     * Method to validate that the stratefy defined in the flow is a strategy allowed.
     *
     * @param string $strategy
     *
     * @throws \Exception
     */
    private function validateStrategy($strategy)
    {
        $aggregationStrategies = $this->getAvailableAggregationStrategies();

        if (!in_array($strategy, $aggregationStrategies)) {
            throw new \Exception(
                sprintf(
                    'strategyRef "%s" is not a valid aggregation strategy for recipient list, accepted values: %s',
                    $strategy,
                    implode(', ', $aggregationStrategies)
                )
            );
        }
    }

    /**
     * Method returns array of available aggregation strategies.
     *
     * @return array
     */
    private function getAvailableAggregationStrategies()
    {
        return [
            self::AGGREGATION_STRATEGY_FIRE_AND_FORGET,
        ];
    }
}
