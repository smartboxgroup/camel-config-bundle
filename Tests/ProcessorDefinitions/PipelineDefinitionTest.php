<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\PipelineDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PipelineDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class PipelineDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PipelineDefinition
     */
    protected $processorDefinition;

    /**
     * @var FlowsBuilderCompilerPass|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flowsBuilderCompilerPassMock;

    public function setUp()
    {
        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'registerProcessor', 'buildItinerary', 'buildEndpoint', 'addToItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));
        $this->flowsBuilderCompilerPassMock->method('buildEndpoint')->willReturn(new Reference(2));

        $this->processorDefinition = new PipelineDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function testBuildProcessor()
    {
        $config = new \SimpleXMLElement(
            '
                <pipeline>
                    <description>some description</description>
                    <to uri="direct://test/c"/>
                    <to uri="direct://test/d"/>
                </pipeline>
            '
        );
        $pipelineDefinition = $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));
        $expectedMethodCalls = [
            [
                'setDescription',
                [
                    'some description',
                ],
            ],
            [
                'setItinerary',
                [
                    new Reference(1),
                ],
            ],
        ];

        $this->assertEquals($expectedMethodCalls, $pipelineDefinition->getMethodCalls());
    }
}
