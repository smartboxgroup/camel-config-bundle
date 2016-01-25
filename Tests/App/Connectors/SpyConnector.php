<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App\Connectors;

use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use JMS\Serializer\Annotation as JMS;

/**
 * Class SpyConnector
 * @package Smartbox\Integration\CamelConfigBundle\Tests\App\Connectors
 */
class SpyConnector extends Connector
{
    /**
     * @JMS\Exclude
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY];

    const OPTION_PATH = 'path';

    public $array = [];

    /**
     * Sends an exchange to the connector
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, array $options)
    {
        /** @var EntityX $x */
        $x = $ex->getIn()->getBody();

        $path = $options[self::OPTION_PATH];

        if(!array_key_exists($path,$this->array)){
            $this->array[$path] = [];
        }

        $this->array[$path][] = $x->getX();
    }

    public static function validateOptions(array $options, $checkComplete = false)
    {
        if(array_key_exists(self::OPTION_EXCHANGE_PATTERN,$options) && $options[self::OPTION_EXCHANGE_PATTERN] != self::EXCHANGE_PATTERN_IN_ONLY){
            throw new InvalidOptionException(self::class,self::OPTION_EXCHANGE_PATTERN,"Exchange pattern not supported");
        }

        if($checkComplete && !array_key_exists(self::OPTION_PATH,$options)){
            throw new InvalidOptionException(self::class,self::OPTION_PATH,"Missing path option");
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
            self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_ONLY
        );
    }

    public function getAvailableOptions(){
        $options = array(
            self::OPTION_PATH => array('Path to store the messages crossing this spy', array()),
        );

        return $options;
    }

    /**
     * @return array
     */
    public function getData($path)
    {
        if (array_key_exists($path, $this->array)) {
            return $this->array[$path];
        }else{
            return [];
        }
    }
}
