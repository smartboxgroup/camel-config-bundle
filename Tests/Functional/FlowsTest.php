<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\Functional;

use Monolog\Logger;
use Smartbox\Integration\CamelConfigBundle\Tests\App\Entity\EntityX;
use Smartbox\Integration\CamelConfigBundle\Tests\BaseKernelTestCase;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

/**
 * Class FlowsTest
 * @package Smartbox\Integration\CamelConfigBundle\Tests\Functional
 */
class FlowsTest extends BaseKernelTestCase{

    /** @var DebugHandler */
    private $loggerHandler;

    public function flowsDataProvider(){
        $this->setUp();
        $parser = new Parser();
        $finder = new Finder();
        $flowsDir = $this->getContainer()->getParameter('smartesb.flows_directories');
        $finder->files()->in($flowsDir);

        $res = array();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if($file->getFilename() == 'test.yml'){
                $res[] = [$file->getRelativePath(),$parser->parse(file_get_contents($file->getRealpath()))];
            }
        }

        return $res;
    }

    /**
     * @dataProvider flowsDataProvider
     * @param $path
     * @param array $conf
     * @throws \Exception
     */
    public function testFlow($path, array $conf){
        if(!array_key_exists('steps',$conf)){
            throw new \Exception("Missing steps");
        }

        /** @var Logger $logger */
        $logger = $this->getContainer()->get('logger');
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof DebugHandler) {
                $this->loggerHandler = $handler;
                break;
            }
        }

        foreach($conf['steps'] as $step){
            $type = $step['type'];
            switch($type){
                case 'handle':
                    $this->handle($step);
                    break;
                case 'checkSpy':
                    $this->checkSpy($step);
                    break;
                case 'consume':
                    $this->consume($step);
                    break;
                case 'expectedException':
                    $this->expectedException($step);
                    break;
                case 'checkLogs':
                    $this->checkLogs($step);
                    break;
            }
        }
    }

    /**
     * @param array $conf
     * @throws \Exception
     * @throws \Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException
     */
    private function handle(array $conf){
        if(!array_key_exists('in',$conf) || !array_key_exists('from',$conf)){
            throw new \Exception("Missing parameter in handle step");
        }

        /** @var ExpressionEvaluator $evaluator */
        $evaluator = $this->getContainer()->get('smartesb.util.evaluator');

        $in = $evaluator->evaluateWithVars($conf['in'],array());

        $message = $this->createMessage(new EntityX($in));
        $handler = $this->getContainer()->get('smartesb.helper')->getHandler('sync');
        $endpointFactory = $this->getContainer()->get('smartesb.endpoint_factory');
        $endpoint = $endpointFactory->createEndpoint($conf['from']);

        /** @var EntityX $result */
        $result = $handler->handle($message,$endpoint)->getBody();

        if (isset($conf['out'])) {
            $out = $evaluator->evaluateWithVars($conf['out'],array('in' => $in));
            $this->assertEquals($out,$result->getX(), "Unexpected result when handling message from: ".$conf['from']);
        }
    }

    /**
     * @param array $conf
     * @throws \Exception
     * @throws \Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException
     */
    private function checkSpy(array $conf){
        if(!array_key_exists('path',$conf) || !array_key_exists('values',$conf)){
            throw new \Exception("Missing parameter in checkSpy step");
        }
        $evaluator = $this->getContainer()->get('smartesb.util.evaluator');

        $expectedValues = [];
        foreach($conf['values'] as $value){
            $expectedValues[] = $evaluator->evaluateWithVars($value,array());
        }

        $values = $this->getContainer()->get('producer.spy')->getData($conf['path']);

        $this->assertEquals($expectedValues,$values, "The spy ".$conf['path']." didn't contain the expected data");
    }

    /**
     * @param array $conf
     * @throws \Exception
     * @throws \Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException
     */
    private function consume(array $conf){
        if(!array_key_exists('uri',$conf) || !array_key_exists('amount',$conf)){
            throw new \Exception("Missing parameter uri in consume step");
        }

        $uri = $conf['uri'];

        /** @var EndpointInterface $endpoint */
        $endpoint = $this->getContainer()->get('smartesb.endpoint_factory')->createEndpoint($uri);
        $endpoint->consume($conf['amount']);
    }

    private function expectedException(array $conf)
    {
        if (!array_key_exists('class',$conf)) {
            $conf['class'] = ProcessingException::class;
        }

        $this->setExpectedException($conf['class']);
    }

    private function checkLogs(array $conf)
    {
        if(!array_key_exists('level', $conf) || !array_key_exists('message', $conf)){
            throw new \Exception("Missing parameter in checkLogs step");
        }

        $level = $conf['level'];
        $message = $conf['message'];

        $this->assertTrue($this->loggerHandler->hasRecordThatContains($message, $level));

    }
}
