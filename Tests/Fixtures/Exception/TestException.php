<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\Fixtures\Exception;

/**
 * Class TestException.
 */
class TestException extends \Exception
{
    public function __construct($message = "This is a test exception")
    {
        parent::__construct($message);
    }
}
