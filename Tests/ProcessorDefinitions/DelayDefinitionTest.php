<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\ProcessorDefinitions;

use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\DelayDefinition;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class DelayDefinitionTest.
 */
class DelayDefinitionTest extends BaseKernelTestCase
{
    /**
     * @var DelayDefinition
     */
    protected $processorDefinition;

    /**
     * @var FlowsBuilderCompilerPass|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flowsBuilderCompilerPassMock;

    public function setUp()
    {
        $this->flowsBuilderCompilerPassMock = $this->getMockBuilder(FlowsBuilderCompilerPass::class)
            ->setMethods(['getBasicDefinition', 'buildItinerary'])
            ->getMock();

        $this->flowsBuilderCompilerPassMock->method('getBasicDefinition')->willReturn(new Definition());
        $this->flowsBuilderCompilerPassMock->method('buildItinerary')->willReturn(new Reference(1));

        $this->processorDefinition = new DelayDefinition();
        $this->processorDefinition->setBuilder($this->flowsBuilderCompilerPassMock);
        $this->processorDefinition->setEvaluator(new ExpressionEvaluator(new ExpressionLanguage()));
    }

    public function testBuildProcessor()
    {
        $config = new \SimpleXMLElement(
            '<delay delayPeriod="2">
                <description>Delay Interceptor</description>
            </delay>'
        );
        $delayDefinition = $this->processorDefinition->buildProcessor($config, $this->flowsBuilderCompilerPassMock->determineProcessorId($config));

        $expectedMethodCalls = [
            [
                'setDelayPeriod',
                [
                    2,
                ],
            ],
            [
                'setDescription',
                [
                    'Delay Interceptor',
                ],
            ],
        ];

        $this->assertEquals($expectedMethodCalls, $delayDefinition->getMethodCalls());
    }
}
