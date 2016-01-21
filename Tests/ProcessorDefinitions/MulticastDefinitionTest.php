<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Processors\Routing\Multicast;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\MulticastDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class MulticastDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class MulticastDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MulticastDefinition
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

        $this->processorDefinition = new MulticastDefinition();
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

                    // Check the processor is a router
                    $this->assertEquals('multicast', $processorType);

                    $calls = $definition->getMethodCalls();

                    // test aggregation strategy is set
                    $aggregationStrategySetter = array_shift($calls);
                    $this->assertEquals(Multicast::AGGREGATION_STRATEGY_FIRE_AND_FORGET, $aggregationStrategySetter[$ARGS][0]);

                    // test description is set
                    $description = array_shift($calls);
                    $this->assertEquals('some description', $description[$ARGS][0]);

                    // expected 4 itineraries setter calls inside the definition
                    $this->assertCount(4, $calls);
                    for ($i=0; $i < 4; $i++) {
                        $this->assertEquals('addItinerary', $calls[$i][$METHOD]);
                    }

                    return new Reference("1");
                }
            );

        $config = new \SimpleXMLElement(
            '<multicast strategyRef="fireAndForget">
                <description>some description</description>
                <to uri="direct://test/a"/>
                <to uri="direct://test/b"/>
                <pipeline>
                    <to uri="direct://test/c"/>
                    <to uri="direct://test/d"/>
                </pipeline>
                <to uri="direct://test/e"/>
            </multicast>');
        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }

    public function testInvalidAggregationStrategy()
    {
        $this->setExpectedException(\Exception::class);

        $config = new \SimpleXMLElement('<multicast strategyRef="invalidStrategy"></multicast>');
        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }
}
