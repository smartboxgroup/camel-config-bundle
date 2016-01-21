<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\FrameworkBundle\Util\ExpressionEvaluator;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\RouterDefinition;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RouterDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class RouterDefinitionTest extends BaseKernelTestCase
{

    /**
     * @var RouterDefinition
     */
    protected $processorDefinition;

    /**
     * @var FlowsBuilderCompilerPass|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flowsBuilderCompilerPassMock;


    public function setUp()
    {
        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'registerService', 'buildItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));

        $this->processorDefinition = new RouterDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
        $this->processorDefinition->setEvaluator(new ExpressionEvaluator());
    }

    /**
     * Test the cases where the itinerary is not build, that's the description and single properties for a when clause and
     * the description in the otherwise case. The itinerary creation should be tested in the flowsBuilderCompilerPass class
     */
    public function testBuildProcessor()
    {
        $this->flowsBuilderCompilerPassMock
            ->expects($this->once())
            ->method('registerService')
            ->willReturnCallback(
                function (Definition $definition, $processorType) {
                    // Check the processor is a router
                    $this->assertEquals('router', $processorType);

                    return new Reference("1");
                }
            );

        $config = new \SimpleXMLElement("<choice><when><description>when description</description><simple>msg.getBody().get('id') == 666</simple></when><otherwise><description>otherwise description</description></otherwise></choice>");
        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }

    /**
     * Tests exception when XML for when clause does not contain anything
     */
    public function testBuildProcessorException()
    {
        $this->setExpectedException('Exception', 'Expression missing in when clause');

        $config = new \SimpleXMLElement("<choice><when></when></choice>");
        $this->processorDefinition->buildProcessor($config, FlowsBuilderCompilerPass::determineProcessorId($config));
    }

}
