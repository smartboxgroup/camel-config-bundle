<?php

namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\Pipeline;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;

/**
 * Class TryCatchDefinition
 * @package Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions
 */
class TryCatchDefinition extends ProcessorDefinition
{
    use UsesEvaluator;

    const INNER_SUFFIX = '_inner';

    const CATCH_CLAUSE = "doCatch";
    const FINALLY_CLAUSE = "doFinally";
    const SIMPLE = "simple";
    const HANDLED = "handled";

    /**
     * @var string
     */
    protected $pipelineClass;

    /**
     * @param string $pipelineClass
     */
    public function setPipelineClass($pipelineClass)
    {
        $this->pipelineClass = $pipelineClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        // Build pipeline
        $pipeline = $this->builder->getBasicDefinition($this->pipelineClass);

        // Build try/catch
        $innerId = $id.self::INNER_SUFFIX;
        $tryCatch = parent::buildProcessor($configNode, $innerId);

        // Build Pipeline itinerary with try/catch as first node
        $itineraryName = $this->getBuilder()->generateNextUniqueReproducibleIdForContext($id);
        $mainItinerary = $this->builder->buildItinerary($itineraryName);
        $pipeline->addMethodCall('setItinerary',[$mainItinerary]);
        $this->builder->addProcessorDefinitionToItinerary($mainItinerary, $tryCatch, $innerId);

        // Configure try/catch and pipeline
        foreach ($configNode as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::DESCRIPTION:
                    $tryCatch->addMethodCall('setDescription', [(string) $nodeValue]);
                    break;
                case self::CATCH_CLAUSE:
                    $clauseParams = $this->buildCatchClauseParams($nodeValue, $id);
                    $tryCatch->addMethodCall('addCatch', $clauseParams);
                    break;
                case self::FINALLY_CLAUSE:
                    $mainItinerary = $this->buildFinallyItineray($nodeValue, $id);
                    $tryCatch->addMethodCall('setFinallyItinerary', array($mainItinerary));
                    break;
                default:
                    $this->builder->addNodeToItinerary($mainItinerary, $nodeName, $nodeValue);
                    break;
            }
        }

        // Return the pipeline
        return $pipeline;
    }

    protected function buildCatchClauseParams($whenConfig,$id)
    {
        $expression = null;
        $itineraryName = $this->getBuilder()->generateNextUniqueReproducibleIdForContext($id);
        $itinerary = $this->builder->buildItinerary($itineraryName);
        $evaluator = $this->getEvaluator();

        foreach ($whenConfig as $nodeName => $nodeValue) {
            switch ($nodeName) {
                case self::HANDLED:
                    $expression = (string) $nodeValue->{self::SIMPLE};
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

    protected function buildFinallyItineray($config, $id)
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
