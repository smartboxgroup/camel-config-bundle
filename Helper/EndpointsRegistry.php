<?php

namespace Smartbox\Integration\ServiceBusBundle\Helper;

/**
 * Class EndpointsRegistry
 * @package Smartbox\Integration\ServiceBusBundle\Helper
 */
class EndpointsRegistry
{
    /** @var array */
    protected $endpoints = [];

    /**
     * @param $endpointId
     */
    public function register($endpointId, $uri)
    {
        $this->endpoints[$uri] = $endpointId;
    }

    /**
     * @return array
     */
    public function getRegisteredEndpoints()
    {
        return $this->endpoints;
    }

    /**
     * @return array
     */
    public function getRegisteredEndpointsUris()
    {
        return array_keys($this->endpoints);
    }

    /**
     * @return array
     */
    public function getRegisteredEndpointsIds()
    {
        return array_values($this->endpoints);
    }
}
