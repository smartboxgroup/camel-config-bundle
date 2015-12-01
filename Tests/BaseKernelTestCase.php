<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests;

use Smartbox\Integration\CamelConfigBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BaseKernelTestCase
 * @package Smartbox\Integration\CamelConfigBundle\Tests
 */
class BaseKernelTestCase extends KernelTestCase
{
    public static function getKernelClass(){
        return AppKernel::class;
    }

    public function setUp(){
        $this->bootKernel();
    }

    public function getContainer(){
        return self::$kernel->getContainer();
    }
}
