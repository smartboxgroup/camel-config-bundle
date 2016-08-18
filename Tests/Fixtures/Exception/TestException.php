<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\Fixtures\Exception;

/**
 * Class TestException.
 */
class TestException extends \Exception
{
    public function __construct()
    {
        parent::__construct('This is a test exception');
    }
}
