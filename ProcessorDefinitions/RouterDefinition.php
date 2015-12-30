<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;

/**
 * Class RouterDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
class RouterDefinition extends ProcessorDefinition
{
    use UsesEvaluator;

    const WHEN = "when";
    const OTHERWISE = "otherwise";
    const SIMPLE = "simple";

    /**
     * @param $configNode array
     * @return Reference
     */
    public function buildProcessor($configNode)
    {
        $def = parent::buildProcessor($configNode);

        // TODO: FETCH ID
        foreach ($configNode as $nodeName => $nodeValue) {

            switch ($nodeName) {
                case self::DESCRIPTION:
                    $def->addMethodCall('setDescription', (string)$nodeValue);
                    break;
                case self::WHEN:
                    $clauseParams = $this->buildWhenClauseParams($nodeValue, $def);
                    $def->addMethodCall('addWhen', $clauseParams);
                    break;
                case self::OTHERWISE:
                    $itinerary = $this->buildOtherwiseItineraryRef($nodeValue);
                    $def->addMethodCall('setOtherwise', array($itinerary));
                    break;
            }
        }

        $reference = $this->builder->registerService($def, 'router');

        return $reference;
    }

    protected function buildWhenClauseParams($whenConfig, Definition $definition = null)
    {
        $expression = null;
        $itinerary = $this->builder->buildItinerary();
        $evaluator = $this->getEvaluator();

        foreach ($whenConfig as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::SIMPLE:
                    $expression = (string)$nodeValue;
                    try {
                        $evaluator->compile($expression, $this->evaluator->getExchangeExposedVars());
                    } catch (\Exception $e) {
                        throw new InvalidConfigurationException(
                            "Given value ({$expression}) should be a valid expression: " . $e->getMessage(),
                            $e->getCode(),
                            $e
                        );
                    }
                    break;

                case self::DESCRIPTION:
                    $description = (string) $nodeValue;
                    $definition->addMethodCall('setDescription', $description);
                    break;

                default:
                    $this->builder->addNodeToItinerary($itinerary, $nodeName, $nodeValue);
                    break;
            }
        }

        if (empty($expression)) {
            throw new \Exception("Expression missing in when clause");
        }

        return array($expression, $itinerary);
    }

    protected function buildOtherwiseItineraryRef($config)
    {
        $itinerary = $this->builder->buildItinerary();

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
