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
            ->setMethods(array('getBasicDefinition', 'registerService', 'buildItinerary', 'buildEndpoint', 'addToItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));
        $this->flowsBuilderCompilerPassMock->method('buildEndpoint')->willReturn(new Reference(2));

        $this->processorDefinition = new PipelineDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function testBuildProcessor()
    {
        $this->flowsBuilderCompilerPassMock
            ->expects($this->once())
            ->method('registerService')
            ->willReturnCallback(
                function (Definition $definition, $processorType) {
                    $METHOD = 0;
                    $ARGS = 1;

                    // Check the processor is a pipeline
                    $this->assertEquals(PipelineDefinition::PIPELINE, $processorType);

                    $calls = $definition->getMethodCalls();

                    // test description is set
                    $description = array_shift($calls);
                    $this->assertEquals('some description', $description[$ARGS][0]);

                    // expected 1 itinerary setter calls inside the definition
                    $this->assertCount(1, $calls);
                    $this->assertEquals('setItinerary', $calls[0][$METHOD]);

                    return new Reference("1");
                }
            );

        $config = new \SimpleXMLElement(
            '
                <pipeline>
                    <description>some description</description>
                    <to uri="direct://test/c"/>
                    <to uri="direct://test/d"/>
                </pipeline>
            '
        );
        $this->processorDefinition->buildProcessor($config);
    }
}
