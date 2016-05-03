<?php
namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderInterface;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\ThrowExceptionDefinition;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated\BadRequestException;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class ThrowExceptionDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class ThrowExceptionDefinitionTest extends BaseKernelTestCase
{
    /** @var ThrowExceptionDefinition */
    protected $processorDefinition;

    /** @var FlowsBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $builderMock;


    public function setUp()
    {
        $this->builderMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'getParameter'))
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
        $config = new \SimpleXMLElement('<throwException ref="exceptions.bad_request"/>');
        $throwExceptionDefinition = $this->processorDefinition->buildProcessor($config, $this->builderMock->determineProcessorId($config));

        $expectedMethodCalls = [
            [
                'setDescription',
                [
                    '',
                ],
            ],
            [
                'setExceptionClass',
                [
                    BadRequestException::class,
                ],
            ],
        ];

        $this->assertEquals($expectedMethodCalls, $throwExceptionDefinition->getMethodCalls());
    }
}
