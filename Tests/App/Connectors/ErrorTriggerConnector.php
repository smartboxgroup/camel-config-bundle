<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App\Connectors;

use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions\SampleRecoverableException;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ErrorTriggerConnector
 * @package Smartbox\Integration\CamelConfigBundle\Tests\App\Connectors
 */
class ErrorTriggerConnector extends Connector
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
     * Sends an exchange to the connector
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
     * Validates the options passed to an connector
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
            Connector::OPTION_EXCHANGE_PATTERN => Connector::EXCHANGE_PATTERN_IN_ONLY
        );
    }

    public function getAvailableOptions(){
        $options = array(
            self::OPTION_RECOVERABLE => array('Whether the errors triggered are recoverable or not', array()),
        );

        return $options;
    }
}
