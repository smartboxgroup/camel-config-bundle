<?php

namespace Smartbox\Integration\ServiceBusBundle\Exceptions;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class BadRequestException
 * @package Smartbox\Integration\ServiceBusBundle\Exceptions
 */
class BadRequestException extends BadRequestHttpException
{

    const ERROR_CODE = 801;
    const ERROR_MESSAGE = "Bad request";

    public function __construct($message)
    {
        parent::__construct(self::ERROR_MESSAGE, null, self::ERROR_CODE);
    }
}