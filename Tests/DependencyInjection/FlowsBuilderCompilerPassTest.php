<?php

namespace Smartbox\Integration\ServiceBusBundle\Tests\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\Handlers\SyncHandler;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Routing\ItinerariesMap;
use Smartbox\Integration\ServiceBusBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\ServiceBusBundle\DependencyInjection\SmartboxIntegrationServiceBusExtension;
use Smartbox\Integration\ServiceBusBundle\Helper\EndpointsRegistry;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class FlowsBuilderCompilerPassTest
 * @package Smartbox\Integration\ServiceBusBundle\Tests\DependencyInjection
 * The test is merely functional as there are many dependencies
 *
 * @coversDefaultClass Smartbox\Integration\ServiceBusBundle\DependencyInjection\FlowsBuilderCompilerPass
 */
class FlowsBuilderCompilerPassTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param ContainerBuilder $container
     */
    public function prepareContainer(ContainerBuilder $container){
        $container->setDefinition('smartesb.registry.endpoints',new Definition(EndpointsRegistry::class));
        $container->setDefinition('smartesb.map.itineraries',new Definition(ItinerariesMap::class));
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
     * @covers ::buildConnector
     * @covers ::findAbstractConnector
     * @covers ::registerConnector
     * @covers ::getConnectorScheme
     * @covers ::addNodeToItinerary
     * @covers ::addToItinerary
     * @covers ::buildHandler
     */
    public function testProcess()
    {
        $compilerPass = new FlowsBuilderCompilerPass();

        // Mock extension interface and its related methods
        /** @var SmartboxIntegrationServiceBusExtension|\PHPUnit_Framework_MockObject_MockObject $extension */
        $extension = $this->getMockBuilder(SmartboxIntegrationServiceBusExtension::class)
            ->setMethods(array('getNamespace', 'getAlias', 'getFlowsDirectories', 'getXsdValidationBasePath', 'load'))
            ->getMock();

        $extension->method('getFlowsDirectories')->willReturn(__DIR__ . '/../Fixtures/FlowsBuilderCompilerPassSuccess');

        $extension->method('getAlias')
            ->will($this->returnValue('smartbox_integration_service_bus'));

        // Mock the container given to the compiler pass
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)
        ->setMethods(array('getParameter', 'findTaggedServiceIds'))
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
                    ['connector.direct.demo', $serviceId],
                    ['connector.custom.business_demo', $serviceId],
                ]
            )
        ;

        $container->registerExtension($extension);
        $this->prepareContainer($container);

        $compilerPass->process($container);
    }


    /**
     * Test exceptions when calling GetBasicDefinition
     */
    public function testGetBasicDefinitionException()
    {
        $class = 'Idontexist';
        $this->setExpectedException('InvalidArgumentException', "$class is not a valid class name");
        $compilerPass = new FlowsBuilderCompilerPass();
        $compilerPass->getBasicDefinition($class);
    }
}