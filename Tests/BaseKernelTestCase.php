<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests;

use Smartbox\CoreBundle\Type\Integer;
use Smartbox\Integration\CamelConfigBundle\Tests\App\AppKernel;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
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

    /**
     * @param null $body
     * @param array $headers
     * @param Context $context
     * @return \Smartbox\Integration\FrameworkBundle\Messages\Message
     */
    protected function createMessage($body = null, $headers = array(), Context $context = null){
        return $this->getContainer()->get('smartesb.message_factory')->createMessage($body,$headers,$context);
    }
}
