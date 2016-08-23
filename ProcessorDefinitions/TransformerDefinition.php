<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class TransformerDefinition.
 */
class TransformerDefinition extends ProcessorDefinition
{
    use UsesEvaluator;

    const SIMPLE = 'simple';

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        $evaluator = $this->getEvaluator();

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $description = (string) $nodeValue;
                    $def->addMethodCall('setDescription', [$description]);
                    break;
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
                    $def->addMethodCall('setExpression', [$expression]);
                    break;
                default:
                    throw new InvalidConfigurationException('Unsupported transform node: "'.$nodeName.'"');
            }
        }

        if (!isset($expression)) {
            throw new InvalidConfigurationException('Transformer should have expression in its definition.');
        }

        return $def;
    }
}
