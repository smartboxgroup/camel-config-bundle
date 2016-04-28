<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\Multicast;
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
            ->setMethods(array('getBasicDefinition', 'registerProcessor', 'buildItinerary', 'buildEndpoint', 'buildProcessor', 'addToItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));
        $this->flowsBuilderCompilerPassMock->method('buildEndpoint')->willReturn(new Reference(2));
        $this->flowsBuilderCompilerPassMock->method('buildProcessor')->willReturn(new Reference(3));

        $this->processorDefinition = new MulticastDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function testBuildProcessor()
    {
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
            </multicast>'
        );
        $multicastDefinition = $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));

        $expectedMethodCalls = [
            [
                'setAggregationStrategy',
                [
                    Multicast::AGGREGATION_STRATEGY_FIRE_AND_FORGET,
                ],
            ],
            [
                'setDescription',
                [
                    'some description',
                ],
            ],
        ];
        for ($i = 0; $i < 4; $i++) {
            $expectedMethodCalls[] = [
                'addItinerary',
                [
                    new Reference(1),
                ],
            ];
        }

        $this->assertEquals($expectedMethodCalls, $multicastDefinition->getMethodCalls());
    }

    public function testInvalidAggregationStrategy()
    {
        $this->setExpectedException(\Exception::class);

        $config = new \SimpleXMLElement('<multicast strategyRef="invalidStrategy"></multicast>');
        $this->processorDefinition->buildProcessor($config,$this->flowsBuilderCompilerPassMock->determineProcessorId($config));
    }
}
