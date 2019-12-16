<?php

namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\ItineraryResolver;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Core\Processors\EndpointProcessor;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SlugHelper;
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
 * Class FlowsBuilderCompilerPass.
 */
class FlowsBuilderCompilerPass implements CompilerPassInterface, FlowsBuilderInterface
{
    const CAMEL_CONTEXT = 'camelContext';
    const ROUTE = 'route';
    const FROM = 'from';
    const TO = 'to';
    const TAG_DEFINITIONS = 'smartesb.definitions';
    const PROCESSOR_ID_PREFIX = '_sme_pr_';
    const ITINERARY_ID_PREFIX = '_sme_it_';
    const ENDPOINT_PREFIX = 'endpoint.';

    /** @var ContainerBuilder */
    protected $container;

    /** @var Definition */
    protected $processorDefinitionsRegistry;

    protected $registeredNames = [];

    protected $registeredNamesPerContext = [];

    /** @var SplFileInfo */
    protected $currentLoadingFile = null;
    protected $currentFileSlug = null;

    protected $currentLoadingVersion = null;
    protected $registeredNamesInFileCount = 1;

    protected $incrementIds = [];

    public static function camelToSnake($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    public static function slugify($text)
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

    public function classUsesDeep($class, $autoload = true)
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

    private function getNextIncrementalId()
    {
        if( !array_key_exists($this->currentFileSlug, $this->incrementIds)){
            $this->incrementIds[$this->currentFileSlug] = 0;
        }

        $this->incrementIds[$this->currentFileSlug]++;

        return $this->currentFileSlug.'_'.$this->incrementIds[$this->currentFileSlug];
    }

    public function getParameter($name)
    {
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

        $definition = new Definition($class, []);

        $traits = $this->classUsesDeep($class);
        foreach ($traits as $trait) {
            switch ($trait) {
                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator':
                    $definition->addMethodCall('setEvaluator', [new Reference('smartesb.util.evaluator')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer':
                    $definition->addMethodCall('setSerializer', [new Reference('jms_serializer')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesValidator':
                    $definition->addMethodCall('setValidator', [new Reference('validator')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher':
                    $definition->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointFactory':
                    $definition->addMethodCall('setEndpointFactory', [new Reference('smartesb.endpoint_factory')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointRouter':
                    $definition->addMethodCall('setEndpointsRouter', [new Reference('smartesb.router.endpoints')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\MessageFactoryAware':
                    $definition->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesCacheService':
                    $definition->addMethodCall('setCacheService', [new Reference('smartcore.cache_service')]);
                    break;

                case 'Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItineraryResolver':
                    $definition->addMethodCall('setItineraryResolver', [new Reference('smartesb.itineray_resolver')]);
                    break;

                case 'Symfony\Component\DependencyInjection\ContainerAwareTrait':
                    $definition->addMethodCall('setContainer', [new Reference('service_container')]);
                    break;
            }
        }

        return $definition;
    }

    /**
     * @param $nodeName
     *
     * @return ProcessorDefinitionInterface
     *
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
     *
     * @return string
     */
    public function generateNextUniqueReproducibleIdForContext($contextId)
    {
        if (!array_key_exists($contextId, $this->registeredNamesPerContext)) {
            $this->registeredNamesPerContext[$contextId] = [];
        }

        $index = count($this->registeredNamesPerContext[$contextId]);

        $id = 'v'.$this->currentLoadingVersion.'.'.sha1($contextId.'_'.$index);
        $this->registeredNamesPerContext[$contextId][] = $id;

        return $id;
    }

    /**
     * @param Definition $definition
     * @param string     $name
     *
     * @return Reference
     */
    public function registerItinerary(Definition $definition, $name)
    {
        $id = self::ITINERARY_ID_PREFIX.'v'.$this->currentLoadingVersion.'.'.$name;

        // Avoid name duplicities
        if (in_array($id, $this->registeredNames)) {
            throw new InvalidConfigurationException('Itinerary name used twice: '.$id);
        }
        $this->registeredNames[] = $id;

        $this->container->setDefinition($id, $definition);
        $definition->setProperty('id', $id);
        $definition->setArguments([$name]);
        $definition->setPublic(true);

        return new Reference($id);
    }

    public function determineProcessorId($config)
    {
        $id = @$config['id'].'';
        if (!$id) {
            $id = self::PROCESSOR_ID_PREFIX.$this->getNextIncrementalId();
        }

        $id = 'v'.$this->currentLoadingVersion.'.'.$id;

        return $id;
    }

    /**
     * @param Definition $definition
     *
     * @return Reference
     */
    public function registerProcessor(Definition $definition, $id)
    {
        if ($this->container->has($id)) {
            throw new InvalidConfigurationException('Processor id used twice: '.$id);
        }

        $this->container->setDefinition($id, $definition);
        $definition->setProperty('id', $id);
        $definition->setPublic(true);

        return new Reference($id);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->processorDefinitionsRegistry = $this->container->getDefinition('smartesb.registry.processor_definitions');

        $processorDefinitionsServices = $container->findTaggedServiceIds(self::TAG_DEFINITIONS, false);
        foreach ($processorDefinitionsServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $this->processorDefinitionsRegistry->addMethodCall('register', [$attributes['nodeName'], new Reference($id)]);
            }
        }

        /** @var SmartboxIntegrationCamelConfigExtension $extension */
        $extension = $container->getExtension('smartbox_integration_camel_config');

        /** @var SmartboxIntegrationFrameworkExtension $frameworkExtension */
        $frameworkExtension = $container->getExtension('smartbox_integration_framework');

        $flowsDirs = $extension->getFlowsDirectories();
        $currentVersion = $frameworkExtension->getFlowsVersion();
        $frozenFlowsDir = $extension->getFrozenFlowsDirectory();

        // Load current version if not frozen
        if (!file_exists($frozenFlowsDir.'/'.$currentVersion)) {
            $this->loadFlowsFromPaths($currentVersion, $flowsDirs);
        }

        // Load frozen versions
        $finder = new Finder();
        $frozenDirs = $finder->directories()->in($frozenFlowsDir)->depth(0);

        /** @var SplFileInfo $frozenDir */
        foreach ($frozenDirs as $frozenDir) {
            $subFinder = new Finder();
            $version = $frozenDir->getRelativePathname();
            $subDirs = $subFinder->directories()->in($frozenDir->getRealPath())->depth(0);

            $paths = [];
            /** @var SplFileInfo $subDir */
            foreach ($subDirs as $subDir) {
                $paths[] = $subDir->getRealPath();
            }

            $this->loadFlowsFromPaths($version, $paths);
        }
    }

    /**
     * Loads the flows in the given $paths for the given $version.
     *
     * @param string $version
     * @param array  $paths
     */
    protected function loadFlowsFromPaths($version, $paths)
    {
        $this->currentLoadingVersion = $version;
        $finder = new Finder();
        $finder->files()->name('*.xml')->in($paths)->sortByName();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $this->currentLoadingFile = $file;
            $this->currentFileSlug = self::slugify(str_replace('.xml', '', $this->currentLoadingFile->getRelativePathname()));
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
                    $this->buildFlow($value, (string) @$value['id']);
                    break;
            }
        }
    }

    /** {@inheritdoc} */
    public function buildFlow($config, $flowName = null)
    {
        // Default naming
        if ($flowName == null) {
            $path = $this->currentLoadingFile->getRelativePathname();
            $flowName = self::slugify(str_replace('.xml', '', $path));
            if ($this->registeredNamesInFileCount > 1) {
                $flowName .= '_'.$this->registeredNamesInFileCount;
            }
            ++$this->registeredNamesInFileCount;
        }

        // Avoid name duplicities
        if (in_array($flowName, $this->registeredNames)) {
            throw new InvalidConfigurationException('Flow name used twice: '.$flowName);
        }

        $flowName = 'v'.$this->currentLoadingVersion.'_'.$flowName;

        $this->registeredNames[] = $flowName;

        // Build stuff..
        $itineraryRef = $this->buildItinerary($flowName);
        $from = null;
        $itineraryNodes = 0;

        foreach ($config as $key => $value) {
            if ($key == self::FROM) {
                $from = (string) @$value['uri'];
            } else {
                $this->addNodeToItinerary($itineraryRef, $key, $value);
                ++$itineraryNodes;
            }
        }

        if (empty($from)) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The flow "%s" defined in "%s" must contain at least an endpoint to consume from it',
                    $flowName,
                    realpath($this->currentLoadingFile->getPathname())
                )
            );
        }

        if ($itineraryNodes == 0) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The flow "%s" defined in "%s" must contain at least one node in its itinerary',
                    $flowName,
                    realpath($this->currentLoadingFile->getPathname())
                )
            );
        }

        $from = ItineraryResolver::getItineraryURIWithVersion($from, $this->currentLoadingVersion);
        $itinerariesRepo = $this->container->getDefinition('smartesb.map.itineraries');
        $itinerariesRepo->addMethodCall('addItinerary', [$from, (string) $itineraryRef]);
    }

    /**
     * @param $name
     * @param $config
     *
     * @return Reference
     *
     * @throws \Exception
     */
    public function buildProcessor($name, $config)
    {
        $definitionService = $this->getDefinitionService($name);
        $definitionService->setDebug($this->container->getParameter('kernel.debug'));

        $id = $this->determineProcessorId($config);
        $def = $definitionService->buildProcessor($config, $id);
        $ref = $this->registerProcessor($def, $id);

        return $ref;
    }

    protected function getDummyIdForURI($uri)
    {
        return self::ENDPOINT_PREFIX.SlugHelper::slugify($uri);
    }

    /**
     * @param $config
     *
     * @return Reference
     *
     * @throws \Exception
     */
    public function buildEndpoint($config)
    {
        $uri = @$config['uri'].'';
        $id = $this->determineProcessorId($config);

        $runtimeBreakpoint = isset($config[ProcessorDefinition::ATTRIBUTE_RUNTIME_BREAKPOINT]) &&
            $config[ProcessorDefinition::ATTRIBUTE_RUNTIME_BREAKPOINT] == true &&
            $this->container->getParameter('kernel.debug')
        ;

        $compiletimeBreakpoint = isset($config[ProcessorDefinition::ATTRIBUTE_COMPILETIME_BREAKPOINT]) &&
            $config[ProcessorDefinition::ATTRIBUTE_COMPILETIME_BREAKPOINT] == true &&
            $this->container->getParameter('kernel.debug')
        ;

        // Use existing endpoint ...
        // ====================================
        // Find by id ... !! Id should be specific for scheme, host, path
        if ($compiletimeBreakpoint && function_exists('xdebug_break')) {
            xdebug_break();
        }

        /*
         *
         * DEBUGGING HINTS
         *
         * In case you are adding a compile time breakpoint in a flow xml xdebug will stop here.
         *
         * The definition of the endpoint you are debugging is extending this method.
         *
         * By continuing executing from here you will have chance to debug the way the endpoint service is built
         * in the container.
         *
         * If you are reading this by chance and wondering how  you can add a compile time breakpoint to endpoint you
         * need to add this to your xml flow file, as part of the processor you want to debug:
         *
         *      <... compiletime-breakpoint="1"/>
         *
         * Then you need to execute the compilation in debug mode with xdebug enabled
         */

        $endpointDef = $this->getBasicDefinition(EndpointProcessor::class);
        $endpointDef->addMethodCall('setURI', [$uri]);

        if (isset($config->description)) {
            $endpointDef->addMethodCall('setDescription', [(string) $config->description]);
        }
        if ($runtimeBreakpoint) {
            $endpointDef->addMethodCall('setRuntimeBreakpoint', [true]);
        }

        return $this->registerProcessor($endpointDef, $id);
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
        $itineraryDef->addMethodCall('addProcessorId', [$processor->__toString()]);
    }

    /**
     * {@inheritdoc}
     */
    public function addProcessorDefinitionToItinerary(Reference $itinerary, Definition $processor, $id = null)
    {
        $id = $this->determineProcessorId(['id' => $id]);
        $ref = $this->registerProcessor($processor, $id);
        $this->addToItinerary($itinerary, $ref);
    }

    /**
     * @param Definition|Reference $itinerary
     * @param string               $nodeName
     * @param $nodeConfig
     *
     * @throws \Exception
     *
     * @internal param $configNode
     */
    public function addNodeToItinerary(Reference $itinerary, $nodeName, $nodeConfig)
    {
        switch ($nodeName) {
            case self::TO:
                $ref = $this->buildEndpoint($nodeConfig);
                $this->addToItinerary($itinerary, $ref);
                break;
            default:
                $ref = $this->buildProcessor($nodeName, $nodeConfig);
                $this->addToItinerary($itinerary, $ref);
                break;
        }
    }
}
