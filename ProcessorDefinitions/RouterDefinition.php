<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;

/**
 * Class RouterDefinition.
 */
class RouterDefinition extends ProcessorDefinition
{
    use UsesEvaluator;

    const WHEN = 'when';
    const OTHERWISE = 'otherwise';
    const SIMPLE = 'simple';

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', (string) $nodeValue);
                    break;
                case self::WHEN:
                    $clauseParams = $this->buildWhenClauseParams($nodeValue, $id);
                    $def->addMethodCall('addWhen', $clauseParams);
                    break;
                case self::OTHERWISE:
                    $itinerary = $this->buildOtherwiseItineraryRef($nodeValue, $id);
                    $def->addMethodCall('setOtherwise', [$itinerary]);
                    break;
            }
        }

        return $def;
    }

    protected function buildWhenClauseParams($whenConfig, $id)
    {
        $expression = null;
        $itineraryName = $this->getBuilder()->generateNextUniqueReproducibleIdForContext($id);
        $itinerary = $this->builder->buildItinerary($itineraryName);
        $evaluator = $this->getEvaluator();

        foreach ($whenConfig as $nodeName => $nodeValue) {
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
                    break;

                default:
                    $this->builder->addNodeToItinerary($itinerary, $nodeName, $nodeValue);
                    break;
            }
        }

        if (empty($expression)) {
            throw new \Exception('Expression missing in when clause');
        }

        return [$expression, $itinerary];
    }

    protected function buildOtherwiseItineraryRef($config, $id)
    {
        $itineraryName = $this->getBuilder()->generateNextUniqueReproducibleIdForContext($id);
        $itinerary = $this->builder->buildItinerary($itineraryName);

        foreach ($config as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    break;
                default:
                    $this->builder->addNodeToItinerary($itinerary, $nodeName, $nodeValue);
                    break;
            }
        }

        return $itinerary;
    }
}
