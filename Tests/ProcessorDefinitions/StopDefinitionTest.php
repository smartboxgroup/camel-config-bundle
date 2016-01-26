<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\StopDefinition;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class StopDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class StopDefinitionTest extends BaseKernelTestCase
{
    /**
     * @var StopDefinition
     */
    protected $processorDefinition;

    /**
     * @var FlowsBuilderCompilerPass|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flowsBuilderCompilerPassMock;

    public function setUp()
    {
        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'buildItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));

        $this->processorDefinition = new StopDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function testBuildProcessorForValidConfiguration()
    {
        $config = new \SimpleXMLElement("<stop><description>Some description of stop processor</description></stop>");

        $stopDefinition = $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));

        $expectedMethodCalls = [
            [
                'setDescription',
                [
                    'Some description of stop processor',
                ],
            ],
        ];

        $this->assertEquals($expectedMethodCalls, $stopDefinition->getMethodCalls());
    }

    public function testBuildProcessorForInvalidConfiguration()
    {
        $config = new \SimpleXMLElement("<stop><wrong_node>this is content of unsupported node</wrong_node></stop>");

        $this->setExpectedException(InvalidConfigurationException::class);

        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }
}
