<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App\Producers;

use Smartbox\Integration\FrameworkBundle\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions\SampleRecoverableException;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ErrorTriggerProducer
 * @package Smartbox\Integration\CamelConfigBundle\Tests\App\Producers
 */
class ErrorTriggerProducer extends Producer
{
    /**
     * @JMS\Exclude
     * @var array
     */
    static public $amountOfErrors = 1;

    /**
     * @JMS\Exclude
     * @var array
     */
    static protected $count = 0;

    const OPTION_RECOVERABLE = 'recoverable';

    /**
     * Sends an exchange to the producer
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, array $options)
    {
        if(self::$count < self::$amountOfErrors){
            $ex->getIn()->setBody(new EntityX(666));
            self::$count++;

            if(@$options[self::OPTION_RECOVERABLE]){
                throw new SampleRecoverableException("test recoverable exception");
            }else{
                throw new \RuntimeException("test exception");
            }
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
    }

    /**
     * Get default options
     *
     * @return array
     */
    function getDefaultOptions()
    {
        return array(
            Producer::OPTION_EXCHANGE_PATTERN => Producer::EXCHANGE_PATTERN_IN_ONLY
        );
    }

    public function getAvailableOptions(){
        $options = array(
            self::OPTION_RECOVERABLE => array('Whether the errors triggered are recoverable or not', array()),
        );

        return $options;
    }
}
