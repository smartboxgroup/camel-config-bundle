<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests;

use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BaseKernelTestCase.
 */
class BaseKernelTestCase extends KernelTestCase
{
    public function setUp()
    {
        $this->bootKernel();
    }

    public function getContainer()
    {
        return self::$kernel->getContainer();
    }

    /**
     * @param null    $body
     * @param array   $headers
     * @param Context $context
     *
     * @return \Smartbox\Integration\FrameworkBundle\Core\Messages\Message
     */
    protected function createMessage($body = null, $headers = [], Context $context = null)
    {
        return $this->getContainer()->get('smartesb.message_factory')->createMessage($body, $headers, $context);
    }
}
