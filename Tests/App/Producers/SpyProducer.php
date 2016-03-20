<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App\Producers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SpyProducer
 * @package Smartbox\Integration\CamelConfigBundle\Tests\App\Producers
 */
class SpyProducer extends Producer implements ConfigurableInterface
{
    const OPTION_PATH = 'path';

    public $array = [];

    /**
     * Sends an exchange to the producer
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        /** @var EntityX $x */
        $x = $ex->getIn()->getBody();

        $path = $options[self::OPTION_PATH];

        if(!array_key_exists($path,$this->array)){
            $this->array[$path] = [];
        }

        $this->array[$path][] = $x->getX();
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
            self::OPTION_PATH => array('Path to store the messages crossing this spy', array()),
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
        $resolver->setDefault(Endpoint::OPTION_EXCHANGE_PATTERN,Endpoint::EXCHANGE_PATTERN_IN_ONLY);
        $resolver->setAllowedValues(Endpoint::OPTION_EXCHANGE_PATTERN,[Endpoint::EXCHANGE_PATTERN_IN_ONLY]);

        $resolver->setRequired(self::OPTION_PATH);
        $resolver->setAllowedTypes(self::OPTION_PATH,['string']);
    }
}
