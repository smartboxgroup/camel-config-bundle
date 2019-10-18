<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\DependencyInjection;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\SmartboxIntegrationCamelConfigExtension;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\Registry\ProcessorDefinitionsRegistry;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\ItinerariesMap;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class FlowsBuilderCompilerPassTest.
 *
 * @coversDefaultClass Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass
 */
class FlowsBuilderCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param ContainerBuilder $container
     */
    public function prepareContainer(ContainerBuilder $container)
    {
        $container->setDefinition('smartesb.registry.processor_definitions', new Definition(ProcessorDefinitionsRegistry::class));
        $container->setDefinition('smartesb.map.itineraries', new Definition(ItinerariesMap::class));
    }

    /**
     * @covers ::process
     * @covers ::loadXMLFlows
     * @covers ::build
     * @covers ::buildFlow
     * @covers ::buildItinerary
     * @covers ::getBasicDefinition
     * @covers ::class_uses_deep
     * @covers ::registerService
     * @covers ::buildProducer
     * @covers ::findAbstractProducer
     * @covers ::registerProducer
     * @covers ::getProducerScheme
     * @covers ::addNodeToItinerary
     * @covers ::addToItinerary
     * @covers ::buildHandler
     *
     * @doesNotPerformAssertions
     */
    public function testProcess()
    {
        $compilerPass = new FlowsBuilderCompilerPass();

        // Mock extension interface and its related methods
        /** @var SmartboxIntegrationCamelConfigExtension|\PHPUnit_Framework_MockObject_MockObject $extension */
        $extension = $this->getMockBuilder(SmartboxIntegrationCamelConfigExtension::class)
            ->setMethods(['getNamespace', 'getAlias', 'getFlowsDirectories', 'getFrozenFlowsDirectory', 'getXsdValidationBasePath', 'load'])
            ->getMock();

        $extension->method('getFlowsDirectories')->willReturn(__DIR__.'/../Fixtures/FlowsBuilderCompilerPassSuccess');
        $extension->method('getFrozenFlowsDirectory')->willReturn(__DIR__.'/../Fixtures/FlowsBuilderCompilerPassSuccess/Frozen');

        $extension->method('getAlias')
            ->will($this->returnValue('smartbox_integration_camel_config'));

        /** @var SmartboxIntegrationFrameworkExtension|\PHPUnit_Framework_MockObject_MockObject $frameworkExtension */
        $frameworkExtension = $this->getMockBuilder(SmartboxIntegrationFrameworkExtension::class)
            ->setMethods(['getNamespace', 'getAlias', 'getFlowsVersion', 'getXsdValidationBasePath', 'load'])
            ->getMock();

        $frameworkExtension
            ->method('getFlowsVersion')
            ->willReturn(0);

        $frameworkExtension->method('getAlias')
            ->willReturn('smartbox_integration_framework');

        // Mock the container given to the compiler pass
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)
        ->setMethods(['getParameter', 'findTaggedServiceIds'])
        ->getMock();

        // The following expectations are tight up to the CamelFlowGetBox sample
        $container
            ->expects($this->any())
            ->method('getParameter')
            ->willReturnMap(
                [
                    ['smartesb.itinerary.class', Itinerary::class],
                ]
            )
        ;

        $serviceId[1] = 'test';
        $container
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->willReturnMap(
                [
                    [FlowsBuilderCompilerPass::TAG_DEFINITIONS, ['serviceId' => [['nodeName' => 'abc']]]],
                    ['producer.direct.demo', $serviceId],
                    ['producer.custom.business_demo', $serviceId],
                ]
            )
        ;

        $container->registerExtension($frameworkExtension);
        $container->registerExtension($extension);
        $this->prepareContainer($container);

        $compilerPass->process($container);
    }

    /**
     * Test exceptions when calling GetBasicDefinition.
     */
    public function testGetBasicDefinitionException()
    {
        $class = 'Idontexist';
        $this->expectException('InvalidArgumentException');
        $compilerPass = new FlowsBuilderCompilerPass();
        $compilerPass->getBasicDefinition($class);
    }
}
