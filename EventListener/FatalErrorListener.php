<?php

namespace Smartbox\Integration\ServiceBusBundle\EventListener;

use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;

/**
 * Class FatalErrorListener
 * @package Smartbox\Integration\ServiceBusBundle\EventListener
 */
class FatalErrorListener
{
    public function onErrorEvent(ProcessingErrorEvent $event)
    {
        if ($event->shouldThrowException()){
            throw $event->getException();
        }
    }
}