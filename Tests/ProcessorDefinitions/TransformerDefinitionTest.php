<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\TransformerDefinition;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class TransformerDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class TransformerDefinitionTest extends BaseKernelTestCase
{
    /**
     * @var TransformerDefinition
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

        $this->processorDefinition = new TransformerDefinition();
        $this->processorDefinition->setEvaluator($container->get('smartesb.util.evaluator'));
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function dataProviderForValidConfiguration()
    {
        return [
            [new \SimpleXMLElement("<transform><description>Some description of transformer processor</description><simple>msg.getBody().get('box').setDescription('test description')</simple></transform>")],
            [new \SimpleXMLElement("<transform><description></description><simple>msg.getBody().get('box').setDescription('test description')</simple></transform>")],
            [new \SimpleXMLElement("<transform><simple>msg.getBody().get('box').setDescription('test description')</simple></transform>")],
        ];
    }

    /**
     * Test the cases where the itinerary is not build, that's the description and single properties for a when clause and
     * the description in the otherwise case. The itinerary creation should be tested in the flowsBuilderCompilerPass class
     *
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
                    // Check the processor is a transformer
                    $this->assertEquals('transformer', $processorType);
                    return new Reference("1");
                }
            );

        $this->processorDefinition->buildProcessor($config);
    }

    public function dataProviderForInvalidConfiguration()
    {
        return [
            [new \SimpleXMLElement("<transform></transform>")],
            [new \SimpleXMLElement("<transform><simple></simple></transform>")],
            [new \SimpleXMLElement("<transform><description>Some description of transformer processor</description></transform>")],
            [new \SimpleXMLElement("<transform><simple>incorrect expression</simple></transform>")],
            [new \SimpleXMLElement("<transform><simple>msg.getBody().get('box').setDescription('test description')abc</simple></transform>")],
            [new \SimpleXMLElement("<transform><wrong_node></wrong_node></transform>")],
            [new \SimpleXMLElement("<transform><description>Some description of transformer processor</description><wrong_node></wrong_node></transform>")],
            [new \SimpleXMLElement("<transform><wrong_node>this is content of unsupported node</wrong_node></transform>")],
        ];
    }

    /**
     * Tests exception when XML for transformer expression is invalid
     *
     * @dataProvider dataProviderForInvalidConfiguration
     *
     * @param $config
     */
    public function testBuildProcessorForInvalidConfiguration($config)
    {
        $this->setExpectedException(InvalidConfigurationException::class);

        $this->processorDefinition->buildProcessor($config);
    }

}
