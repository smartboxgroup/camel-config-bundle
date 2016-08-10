<?php
namespace Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions;


use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\Throttler;

class ThrottlerDefinition extends ProcessorDefinition {

    /** @var  string */
    protected $processorClass = Throttler::class;

    /**
     * {@inheritdoc}
     */
    public function buildProcessor($configNode, $id)
    {
        $def = parent::buildProcessor($configNode, $id);

        // Description
        $timeMs = (int)$configNode->attributes()->{'timePeriodMillis'};
        if(!$timeMs && is_int($timeMs) && ! $timeMs >= 0){
            throw new \RuntimeException("The attribute timePeriodMillis of the throttler processor must be defined and be an integer >= 0");
        }
        $def->addMethodCall('setPeriodMs', array($timeMs));

        // Ref
        $expression = (string)$configNode->{'simple'};
        if(!$expression){
            throw new \RuntimeException("An expression must be defined for the throttler processor");
        }
        $def->addMethodCall('setLimitExpression', array($expression));

        return $def;
    }

}