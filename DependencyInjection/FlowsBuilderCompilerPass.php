<?php

namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;
use Smartbox\Integration\FrameworkBundle\Helper\EndpointHelper;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\ProcessorDefinition;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\ProcessorDefinitionInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class FlowsBuilderCompilerPass
 * @package Smartbox\Integration\CamelConfigBundle\DependencyInjection
 */
class FlowsBuilderCompilerPass implements CompilerPassInterface, FlowsBuilderInterface
{
    const CAMEL_CONTEXT = "camelContext";
    const ROUTE = "route";
    const FROM = "from";
    const TO = "to";
    const TAG_DEFINITIONS = "smartesb.definitions";
    const PIPELINE = 'pipeline';

    /** @var  ContainerBuilder */
    protected $container;

    /** @var Definition */
    protected $endpointsRegistry;

    /** @var Definition */
    protected $processorDefinitionsRegistry;

    protected $registeredNames =[];

    protected $registeredNamesPerContext =[];

    /** @var SplFileInfo */
    protected $currentFile = null;
    protected $registeredNamesInFileCount = 1;

    protected static $incrementId = 0;

    static public function camelToSnake($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    static public function slugify($text)
    {
        $text = self::camelToSnake($text);
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '_', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'na';
        }

        return $text;
    }

    function classUsesDeep($class, $autoload = true)
    {
        $traits = [];
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_unique($traits);
    }

    private static function getNextIncrementalId(){
        self::$incrementId++;
        return self::$incrementId;
    }

    public function getParameter($name){
        return $this->container->getParameter($name);
    }

    /**
     * Creates a basic service definition for the given class, injecting the typical dependencies according with the
     * traits the class uses.
     *
     * @return Definition
     */
    public function getBasicDefinition($class)
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("$class is not a valid class name");
        }

        $definition = new Definition($class, array());

        $traits = $this->classUsesDeep($class);
        foreach ($traits as $trait) {
            switch ($trait) {
                case 'Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator':
                    $definition->addMethodCall('setEvaluator', array(new Reference('smartesb.util.evaluator')));
                    break;

                case 'Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer':
                    $definition->addMethodCall('setSerializer', array(new Reference('serializer')));
                    break;

                case 'Smartbox\Integration\FrameworkBundle\Traits\UsesValidator':
                    $definition->addMethodCall('setValidator', array(new Reference('validator')));
                    break;

                case 'Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher':
                    $definition->addMethodCall('setEventDispatcher', array(new Reference('event_dispatcher')));
                    break;

                case 'Smartbox\Integration\FrameworkBundle\Traits\UsesConnectorsRouter':
                    $definition->addMethodCall('setConnectorsRouter', array(new Reference('smartesb.router.connectors')));
                    break;

                case 'Smartbox\Integration\FrameworkBundle\Traits\MessageFactoryAware':
                    $definition->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
                    break;
            }
        }

        return $definition;
    }

    /**
     * @param $nodeName
     * @return ProcessorDefinitionInterface
     * @throws \Exception
     */
    protected function getDefinitionService($nodeName)
    {
        /** @var ProcessorDefinitionInterface $definition */
        $definition = $this->container->get('smartesb.registry.processor_definitions')->findDefinition($nodeName);

        $definition->setBuilder($this);

        return $definition;
    }

    /**
     * @param $contextId
     * @return string
     */
    public function generateNextUniqueReproducibleIdForContext($contextId) {
        if(!array_key_exists($contextId, $this->registeredNamesPerContext)){
            $this->registeredNamesPerContext[$contextId] = [];
        }

        $index = count($this->registeredNamesPerContext[$contextId]);

        $id = sha1($contextId.$index);
        $this->registeredNamesPerContext[$contextId][] = $id;
        return $id;
    }

    /**
     * @param Definition $definition
     * @param string $name
     * @return Reference
     */
    public function registerItinerary(Definition $definition, $name)
    {
        $id = 'smartesb.itinerary.'.$name;

        // Avoid name duplicities
        if(in_array($id, $this->registeredNames)){
            throw new InvalidConfigurationException("Itinerary name used twice: ".$id);
        }
        $this->registeredNames[] = $id;

        $this->container->setDefinition($id, $definition);
        $definition->setProperty('id', $id);
        $definition->setArguments(array($name));

        return new Reference($id);
    }

    public static function determineProcessorId($config){
        $id = @$config["id"]."";
        if(!$id){
            $id = 'smartesb.processor.' . self::getNextIncrementalId();
        }
        return $id;
    }

    /**
     * @param Definition $definition
     * @return Reference
     */
    public function registerProcessor(Definition $definition, $id)
    {
        $this->container->setDefinition($id, $definition);
        $definition->setProperty('id', $id);

        return new Reference($id);
    }

    /**
     * @param ContainerBuilder $container
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->endpointsRegistry = $this->container->getDefinition('smartesb.registry.endpoints');
        $this->processorDefinitionsRegistry = $this->container->getDefinition('smartesb.registry.processor_definitions');

        $processorDefinitionsServices = $container->findTaggedServiceIds(self::TAG_DEFINITIONS);
        foreach ($processorDefinitionsServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $this->processorDefinitionsRegistry->addMethodCall('register', array($attributes['nodeName'], new Reference($id)));
            }
        }

        /** @var SmartboxIntegrationCamelConfigExtension $extension */
        $extension = $container->getExtension('smartbox_integration_camel_config');

        /** @var SmartboxIntegrationFrameworkExtension $frameworkExtension */
        $frameworkExtension = $container->getExtension('smartbox_integration_framework');

        $flowsDirs = $extension->getFlowsDirectories();
        $frozenFlowsDir = $extension->getFrozenFlowsDirectory();
        $version = $frameworkExtension->getFlowsVersion();

        if(file_exists($frozenFlowsDir.'/'.$version)){
            $finder = new Finder();
            $dirs = $finder->directories()->in($frozenFlowsDir.'/'.$version)->depth(0);
            $paths = [];

            /** @var SplFileInfo $dir */
            foreach($dirs as $dir){
                $paths[] = $dir->getRealPath();
            }

            $this->loadFlowsFromPaths($paths);
        }else{
            $this->loadFlowsFromPaths($flowsDirs);
        }

    }

    protected function loadFlowsFromPaths($path){
        $finder = new Finder();
        $finder->files()->name('*.xml')->in($path)->sortByName();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $this->currentFile = $file;
            $this->registeredNamesInFileCount = 1;
            $this->loadXMLFlows($file->getRealpath());
        }
    }

    protected function loadXMLFlows($path)
    {
        $configs = new \SimpleXMLElement(file_get_contents($path));
        $this->build($configs);
    }

    /**
     * @param \SimpleXMLElement $config
     */
    public function build(\SimpleXMLElement $config)
    {
        foreach ($config as $key => $value) {
            switch ($key) {
                case self::CAMEL_CONTEXT:
                    $this->build($value);
                    break;
                case self::ROUTE:
                    $this->buildFlow($value,(string)@$value['id']);
                    break;
            }
        }
    }

    /** {@inheritdoc} */
    public function buildFlow($config, $flowName = null)
    {
        // Default naming
        if($flowName == null){
            $path = $this->currentFile->getRelativePathname();
            $flowName = self::slugify(str_replace('.xml','',$path));
            if($this->registeredNamesInFileCount > 1){
                $flowName .= '_'.$this->registeredNamesInFileCount;
            }
            $this->registeredNamesInFileCount++;
        }

        // Avoid name duplicities
        if(in_array($flowName,$this->registeredNames)){
            throw new InvalidConfigurationException("Flow name used twice: ".$flowName);
        }
        $this->registeredNames[] = $flowName;

        // Build stuff..
        $itineraryRef = $this->buildItinerary($flowName);
        $from = null;
        $itineraryNodes = 0;

        foreach ($config as $key => $value) {
            if ($key == self::FROM) {
                $from = (string)@$value["uri"];
            } else {
                $this->addNodeToItinerary($itineraryRef, $key, $value);
                $itineraryNodes++;
            }
        }

        if(empty($from)){
            throw new InvalidConfigurationException(
                sprintf(
                    'The flow "%s" defined in "%s" must contain at least an endpoint to consume from it',
                    $flowName,
                    realpath($this->currentFile->getPathname())
                )
            );
        }

        if($itineraryNodes == 0){
            throw new InvalidConfigurationException(
                sprintf(
                    'The flow "%s" defined in "%s" must contain at least one node in its itinerary',
                    $flowName,
                    realpath($this->currentFile->getPathname())
                )
            );
        }

        $itinerariesRepo = $this->container->getDefinition('smartesb.map.itineraries');
        $itinerariesRepo->addMethodCall('addItinerary',array($from,(string)$itineraryRef));
    }

    /**
     * @param $name
     * @param $config
     * @return Reference
     * @throws \Exception
     */
    public function buildProcessor($name, $config)
    {
        $definitionService = $this->getDefinitionService($name);
        $definitionService->setDebug($this->container->getParameter('kernel.debug'));

        $id = self::determineProcessorId($config);
        $def =  $definitionService->buildProcessor($config,$id);
        $ref = $this->registerProcessor($def,$id);
        return $ref;
    }

    /**
     * @param Definition $definition
     * @param string $id
     * @param string $uri
     * @return Reference
     */
    public function registerEndpoint(Definition $definition, $id, $uri)
    {
        $definition->setProperty('id', $id);
        $this->container->setDefinition($id, $definition);
        $this->endpointsRegistry->addMethodCall('register',array($id, $uri));

        return new Reference($id);
    }

    /**
     * @param $config
     * @return Reference
     * @throws \Exception
     */
    public function buildEndpoint($config)
    {
        $uri = @$config["uri"]."";
        $id = @$config["id"]."";

        $runtimeBreakpoint = isset($config[ProcessorDefinition::ATTRIBUTE_RUNTIME_BREAKPOINT]) &&
                             $config[ProcessorDefinition::ATTRIBUTE_RUNTIME_BREAKPOINT] == true &&
                             $this->container->getParameter('kernel.debug')
        ;

        $compiletimeBreakpoint = isset($config[ProcessorDefinition::ATTRIBUTE_COMPILETIME_BREAKPOINT]) &&
                                 $config[ProcessorDefinition::ATTRIBUTE_COMPILETIME_BREAKPOINT] == true &&
                                 $this->container->getParameter('kernel.debug')
        ;

        if (!$id || empty($id)) {
            $id = EndpointHelper::getIdForURI($uri);
        }

        // Use existing endpoint ...
        // ====================================
        // Find by id ... !! Id should be specific for scheme, host, path
        if ($this->container->has($id)) {
            return new Reference($id);
        }else{
            if ($compiletimeBreakpoint && function_exists('xdebug_break')) {
                xdebug_break();
            }

            $endpointDef = $this->getBasicDefinition(Endpoint::class);
            $endpointDef->addMethodCall('setURI', array($uri));
            if (isset($config->description)) {
                $endpointDef->addMethodCall('setDescription', array((string) $config->description));
            };
            if ($runtimeBreakpoint) {
                $endpointDef->addMethodCall('setRuntimeBreakpoint', [true]);
            }
            return $this->registerEndpoint($endpointDef, $id, $uri);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildItinerary($name)
    {
        $itineraryClass = $this->container->getParameter('smartesb.itinerary.class');
        $def = $this->getBasicDefinition($itineraryClass);

        return $this->registerItinerary($def, $name);
    }

    /**
     * @param Reference $itinerary
     * @param Reference $processor
     */
    public function addToItinerary(Reference $itinerary, Reference $processor)
    {
        $itineraryDef = $this->container->getDefinition($itinerary);
        $itineraryDef->addMethodCall('addProcessor', [$processor]);
    }

    /**
     * @param Definition|Reference $itinerary
     * @param string $nodeName
     * @param $nodeConfig
     * @throws \Exception
     * @internal param $configNode
     */
    public function addNodeToItinerary(Reference $itinerary, $nodeName, $nodeConfig)
    {
        switch ($nodeName) {
            case self::TO:
                $ref = $this->buildEndpoint($nodeConfig);
                $this->addToItinerary($itinerary, $ref);
                break;
            case self::PIPELINE:
                foreach ($nodeConfig as $subNodeName => $subNodeValue) {
                    $this->addNodeToItinerary($itinerary, $subNodeName, $subNodeValue);
                }
                break;
            default:
                $ref = $this->buildProcessor($nodeName, $nodeConfig);
                $this->addToItinerary($itinerary, $ref);
                break;
        }
    }
}