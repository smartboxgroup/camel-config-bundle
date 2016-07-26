<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\ProcessDefinition;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\TransformerDefinition;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ProcessDefinitionTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions
 */
class ProcessDefinitionTest extends BaseKernelTestCase
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

        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(array('getBasicDefinition', 'registerService', 'buildItinerary'))
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));

        $this->processorDefinition = new ProcessDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
    }

    public function dataProviderForValidConfiguration()
    {
        return [
            [
                new \SimpleXMLElement("<process ref=\"processor_id\"><description>Some description of 'Process' processor</description></process>"),
                [
                    [
                        'setProcessor',
                        [
                            new Reference('processor_id')
                        ]
                    ],
                    [
                        'setDescription',
                        [
                            'Some description of \'Process\' processor',
                        ],
                    ]
                ]
            ],
            [
                new \SimpleXMLElement("<process ref=\"processor_id\"><description></description></process>"),
                [
                    [
                        'setProcessor',
                        [
                            new Reference('processor_id')
                        ]
                    ],
                    [
                        'setDescription',
                        [
                            '',
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForValidConfiguration
     *
     * @param $config
     * @param array $expectedMethodCalls
     */
    public function testBuildProcessorForValidConfiguration($config, $expectedMethodCalls)
    {
        $processDefinition = $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));

        $this->assertEquals($expectedMethodCalls, $processDefinition->getMethodCalls());
    }

    public function dataProviderForInvalidConfiguration()
    {
        return [
            'Missing ref parameter in process node' => [new \SimpleXMLElement("<process></process>")],
            'Empty ref parameter in process node' => [new \SimpleXMLElement("<process ref=\"\"></process>")],
            'Empty ref parameter in process node (with provided description)' => [new \SimpleXMLElement("<process ref=\"\"><description>Some description</description></process>")],
            'Unsupported node in process node' => [new \SimpleXMLElement("<process><wrong_node></wrong_node></process>")],
            'Unsupported node in process node (with provided content of wrong_node)' => [new \SimpleXMLElement("<process><wrong_node>this is content of unsupported node</wrong_node></process>")],
            'Unsupported node in process node (with provided description)' => [new \SimpleXMLElement("<process><description>Some description</description><wrong_node></wrong_node></process>")],
        ];
    }

    /**
     * Tests exception when process node is invalid
     *
     * @dataProvider dataProviderForInvalidConfiguration
     *
     * @param $config
     */
    public function testBuildProcessorForInvalidConfiguration($config)
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));
    }

}
