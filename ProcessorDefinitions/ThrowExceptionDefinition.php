<?php

namespace Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Exceptions\ExchangeAwareInterface;
use Symfony\Component\DependencyInjection\Reference;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ThrowExceptionDefinition
 * @package Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions
 */
class ThrowExceptionDefinition extends ProcessorDefinition{
    const REF = "ref";
    const PREFIX = 'throw_exception';

    /**
     * @param $configNode \SimpleXMLElement
     * @return Reference
     */
    public function buildProcessor($configNode)
    {
        $def = $this->getBasicDefinition();

        // Description
        $description = (string)$configNode->{'ref'};
        $def->addMethodCall('setDescription', array($description));

        // Ref
        $ref = (string)$configNode->attributes()->{'ref'};
        $exceptionClass = $this->builder->getParameter($ref.".class");

        if(!$exceptionClass){
            throw new \RuntimeException("The exception reference $ref was not found");
        }

        if(     empty($exceptionClass)
            ||  !class_exists($exceptionClass)
            ||  !is_a($exceptionClass,"Exception",true)){

            throw new \RuntimeException("$exceptionClass is not a valid exception class");
        }

        $def->addMethodCall('setExceptionClass', array($exceptionClass));

        $reference = $this->builder->registerService($def, self::PREFIX);

        return $reference;
    }
}