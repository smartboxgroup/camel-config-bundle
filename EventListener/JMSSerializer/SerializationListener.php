<?php

namespace Smartbox\Integration\ServiceBusBundle\EventListener\JMSSerializer;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GenericSerializationVisitor;

/**
 * Class SerializationListener
 * @package Smartbox\Integration\ServiceBusBundle\EventListener\JMSSerializer
 */
class SerializationListener
{
    public function onPostSerialize(ObjectEvent $event)
    {
        $visitor = $event->getVisitor();
        $object = $event->getObject();
        $attributes = $event->getContext()->attributes;

        if ($visitor instanceof GenericSerializationVisitor && is_object($object)) {
            // check if we are serializing for logs
            if($attributes->contains('groups') && in_array('logs', $attributes->get('groups')->get())) {
                $visitor->addData('_object', get_class($object));
            }
        }
    }
}
