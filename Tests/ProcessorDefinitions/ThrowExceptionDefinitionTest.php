<?php
namespace Smartbox\Integration\ServiceBusBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\ServiceBusBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\ServiceBusBundle\Exceptions\BadRequestException;
use Smartbox\Integration\ServiceBusBundle\ProcessorDefinitions\ThrowExceptionDefinition;
use Smartbox\Integration\ServiceBusBundle\Tests\BaseKernelTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ThrowExceptionDefinitionTest extends BaseKernelTestCase
{

    /** @var  ThrowExceptionDefinition */
    protected $processorDefinition;
    protected $builderMock;


    public function setUp()
    {
        $this->builderMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'getParameter', 'registerService'))
            ->getMock();

        $this->builderMock->method('getParameter')->willReturnMap(
            array(
                array('exceptions.bad_request.class', BadRequestException::class)
            )
        );

        $this->builderMock->method('getBasicDefinition')->willReturn(new Definition());

        $this->processorDefinition = new ThrowExceptionDefinition();
        $this->processorDefinition->setBuilder($this->builderMock);
    }


    public function testShouldBuildProcessor()
    {
        $this->builderMock
            ->expects($this->once())
            ->method('registerService')->willReturnCallback(
                function (Definition $definition, $prefix) {
                    // Check prefix
                    $this->assertEquals(ThrowExceptionDefinition::PREFIX, $prefix);

                    // Check setExceptionClass
                    $this->assertContains(
                        array('setExceptionClass', array(BadRequestException::class)),
                        $definition->getMethodCalls()
                    );

                    return new Reference("xxx");
                }
            );

        $config = new \SimpleXMLElement('<throwException ref="exceptions.bad_request"/>');
        $this->processorDefinition->buildProcessor($config);
    }


}