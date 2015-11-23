<?php

namespace Smartbox\Integration\ServiceBusBundle\Logging;

use Monolog\Processor\IntrospectionProcessor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LogProcessor
 * @package Smartbox\Integration\ServiceBusBundle\Logging
 */
class LogProcessor
{
    const TRANSACTION_ID_ATTRIBUTE = 'TRANSACTION_ID';

    /** @var  RequestStack */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        if($this->requestStack && $this->requestStack->getMasterRequest()){
            $record['transaction_id'] = @$this->requestStack->getMasterRequest()->server->get(self::TRANSACTION_ID_ATTRIBUTE);
        }

        return $record;
    }
}
