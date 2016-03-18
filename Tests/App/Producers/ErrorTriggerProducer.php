<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App\Producers;

use Smartbox\Integration\FrameworkBundle\Endpoints\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions\SampleRecoverableException;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ErrorTriggerProducer
 * @package Smartbox\Integration\CamelConfigBundle\Tests\App\Producers
 */
class ErrorTriggerProducer extends Producer implements ConfigurableInterface
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
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();

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
     *  Key-Value array with the option name as key and the details as value
     *
     *  [OptionName => [description, array of valid values],..]
     *
     * @return array
     */
    public function getOptionsDescriptions()
    {
        $options = array(
            self::OPTION_RECOVERABLE => array('Whether the errors triggered are recoverable or not', array()),
        );

        return $options;
    }

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options
     *
     * @param OptionsResolver $resolver
     * @return mixed
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::OPTION_RECOVERABLE);
        $resolver->setDefault(Endpoint::OPTION_EXCHANGE_PATTERN,Endpoint::EXCHANGE_PATTERN_IN_ONLY);
        $resolver->setAllowedValues(Endpoint::OPTION_EXCHANGE_PATTERN,[Endpoint::EXCHANGE_PATTERN_IN_ONLY]);
    }
}
