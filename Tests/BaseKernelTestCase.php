<?php

namespace Smartbox\Integration\ServiceBusBundle\Tests;

use Smartbox\Integration\ServiceBusBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BaseKernelTestCase extends KernelTestCase {
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