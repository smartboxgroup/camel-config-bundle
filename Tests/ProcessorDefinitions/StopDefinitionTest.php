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
        static::bootKernel();
        $container = static::$kernel->getContainer();

        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'registerService', 'buildItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));

        $this->processorDefinition = new StopDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function dataProviderForValidConfiguration()
    {
        return [
            [new \SimpleXMLElement("</stop>")],
            [new \SimpleXMLElement("<stop></stop>")],
            [new \SimpleXMLElement("<stop><description></description></stop>")],
            [new \SimpleXMLElement("<stop><description>Some description of stoper processor</description></stop>")],
        ];
    }

    /**
     * @dataProvider dataProviderForValidConfiguration
     *
     * @param $config
     */
    public function testBuildProcessorForValidConfiguration($config)
    {
        $this->flowsBuilderCompilerPassMock
            ->expects($this->once())
            ->method('registerService')
            ->willReturnCallback(
                function (Definition $definition, $processorType) {
                    // Check the processor is a "stop"
                    $this->assertEquals(StopDefinition::STOP, $processorType);
                    return new Reference("1");
                }
            );

        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }

    public function dataProviderForInvalidConfiguration()
    {
        return [
            [new \SimpleXMLElement("<stop><wrong_node></wrong_node></stop>")],
            [new \SimpleXMLElement("<stop><wrong_node>this is content of unsupported node</wrong_node></stop>")],
        ];
    }

    /**
     * @dataProvider dataProviderForInvalidConfiguration
     *
     * @param $config
     */
    public function testBuildProcessorForInvalidConfiguration($config)
    {
        $this->setExpectedException(InvalidConfigurationException::class);

        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }
}
