<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\Fixtures\Exception;

/**
 * Class TestException
 * @package Smartbox\Integration\CamelConfigBundle\Tests\Fixtures\Exception
 */
class TestException extends \Exception
{
    public function __construct()
    {
        parent::__construct('This is a test exception');
    }
}
