<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App\Producers;

use Smartbox\Integration\FrameworkBundle\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use Smartbox\Integration\FrameworkBundle\Traits\MessageFactoryAware;

/**
 * Class HelperProducer
 * @package Smartbox\Integration\CamelConfigBundle\Tests\App\Producers
 */
class HelperProducer extends Producer implements ProducerInterface {

    const OPTION_OPERATION = 'operation';
    const OPTION_OPERAND = 'operand';

    const OPERATION_MULTIPLY = 'multiply';
    const OPERATION_ADD = 'add';

    /**
     * Sends an exchange to the producer
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, array $options)
    {
        /** @var EntityX $x */
        $x = $ex->getIn()->getBody();
        if(empty($x) || ! ($x instanceof EntityX)){
            throw new \InvalidArgumentException("Expected entity of type EntityX");
        }

        $operand = (int) @$options[self::OPTION_OPERAND];

        switch(@$options[self::OPTION_OPERATION]){
            case self::OPERATION_MULTIPLY:
                $message = $this->messageFactory->createMessage(new EntityX($x->getX() * $operand));
                $ex->setOut($message);
                break;
            case self::OPERATION_ADD:
                $message = $this->messageFactory->createMessage(new EntityX($x->getX() + $operand));
                $ex->setOut($message);
                break;
        }
    }

    /**
     * Validates the options passed to an producer
     *
     * @param array $options
     * @throws InvalidOptionException in case one of the options is not valid
     */
    public static function validateOptions(array $options, $checkComplete = false)
    {
        if($checkComplete && !array_key_exists(self::OPTION_OPERATION,$options)){
            throw new InvalidOptionException(self::class,self::OPTION_OPERATION,"Missing option ".self::OPTION_OPERATION);
        }

        if($checkComplete && !array_key_exists(self::OPTION_OPERAND,$options)){
            throw new InvalidOptionException(self::class,self::OPTION_OPERAND,"Missing option ".self::OPTION_OPERATION);
        }
    }

    /**
     * Get default options
     *
     * @return array
     */
    function getDefaultOptions()
    {
        return array(
            self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_OUT
        );
    }

    public function getAvailableOptions(){
        $options = array(
            self::OPTION_OPERATION => array('Operation to apply to the EntityX in the body of the incoming messages', array(
                self::OPERATION_ADD => 'Adds <comment>operand</comment> to the entityX value',
                self::OPERATION_MULTIPLY => 'Multiplies <comment>operand</comment> by the entityX value'
            )),
            self::OPTION_OPERAND => array('Operand to use', array()),
        );

        return $options;
    }
}