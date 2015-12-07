<?php


namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;


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
    const TAG_DEFINITION = "smartesb.definitions.";
    const PIPELINE = 'pipeline';

    /** @var  ContainerBuilder */
    protected $container;

    /** @var  Definition */
    protected $endpointsRegistry;

    protected $registeredNames =[];

    /** @var SplFileInfo */
    protected $currentFile = null;
    protected $registeredNamesInFileCount = 1;

    protected $incrementId = 0;

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

    public function getNextIncrementalId(){
        $this->incrementId++;
        return $this->incrementId;
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
            }
        }

        return $definition;
    }

    /**
     * @param $tagSuffix
     * @param bool $force
     * @return ProcessorDefinitionInterface
     * @throws \Exception
     */
    protected function getDefinitionService($tagSuffix, $force = true)
    {
        $ids = $this->container->findTaggedServiceIds(self::TAG_DEFINITION.$tagSuffix);
        $ids = array_keys($ids);

        if (count($ids) == 0) {
            if ($force) {
                throw new \Exception("No definition service found for processor $tagSuffix");
            } else {
                return null;
            }
        } else {
            if (count($ids) > 1) {
                throw new \Exception(
                    "Found more than one definition service for processor $tagSuffix: ".join(", ", $ids)
                );
            }
        }

        /** @var ProcessorDefinitionInterface $definition */
        $definition = $this->container->get($ids[0]);
        if (!$definition instanceof ProcessorDefinition) {
            throw new \Exception("Found service tagged as processor definition service but it's not really such");
        }

        $definition->setBuilder($this);

        return $definition;
    }

    /**
     * @param Definition $definition
     * @param string $prefix
     * @return Reference
     */
    public function registerService(Definition $definition, $prefix)
    {
        $id = 'smartesb.'.$prefix.'.'.$this->getNextIncrementalId();
        $this->container->setDefinition($id, $definition);
        $definition->setProperty('id', $id);

        return new Reference($id);
    }


    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->endpointsRegistry = $this->container->getDefinition('smartesb.registry.endpoints');
        /** @var SmartboxIntegrationCamelConfigExtension $extension */
        $extension = $container->getExtension('smartbox_integration_camel_config');
        $flowsDirs = $extension->getFlowsDirectories();

        $finder = new Finder();
        $finder->files()->in($flowsDirs)->sortByName();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if($file->getExtension() == 'xml'){
                $this->currentFile = $file;
                $this->registeredNamesInFileCount = 1;
                $this->loadXMLFlows($file->getRealpath());
            }
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
    public function buildFlow($config, $name = null)
    {
        // Default naming
        if($name == null){
            $path = $this->currentFile->getRelativePathname();
            $name = self::slugify(str_replace('.xml','',$path));
            if($this->registeredNamesInFileCount > 1){
                $name .= '_'.$this->registeredNamesInFileCount;
            }
            $this->registeredNamesInFileCount++;
        }

        // Avoid name duplicities
        if(in_array($name,$this->registeredNames)){
            throw new InvalidConfigurationException("Route name used twice: ".$name);
        }
        $this->registeredNames[] = $name;

        // Build stuff..
        $itinerary = $this->buildItinerary($name);
        $from = null;
        $itineraryNodes = 0;

        foreach ($config as $key => $value) {
            if ($key == self::FROM) {
                $from = (string)@$value["uri"];
            } else {
                $this->addNodeToItinerary($itinerary, $key, $value);
                $itineraryNodes++;
            }
        }

        if(empty($from)){
            throw new InvalidConfigurationException(
                sprintf(
                    'The flow "%s" defined in "%s" must contain at least an endpoint to consume from it',
                    $name,
                    realpath($this->currentFile->getPathname())
                )
            );
        }

        if($itineraryNodes == 0){
            throw new InvalidConfigurationException(
                sprintf(
                    'The flow "%s" defined in "%s" must contain at least one node in its itinerary',
                    $name,
                    realpath($this->currentFile->getPathname())
                )
            );
        }

        $itinerariesRepo = $this->container->getDefinition('smartesb.map.itineraries');
        $itinerariesRepo->addMethodCall('addItinerary',array($from,(string)$itinerary));
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

        return $definitionService->buildProcessor($config);
    }

    /**
     * @param Definition $definition
     * @param string $id
     * @return Reference
     */
    public function registerEndpoint(Definition $definition, $id,$uri)
    {
        $definition->setProperty('id', $id);
        $this->container->setDefinition($id, $definition);
        $this->endpointsRegistry->addMethodCall('register',array($id,$uri));

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


        if (!$id || empty($id)) {
            $id = EndpointHelper::getIdForURI($uri);
        }

        // Use existing endpoint ...
        // ====================================
        // Find by id ... !! Id should be specific for scheme, host, path
        if ($this->container->has($id)) {
            return new Reference($id);
        }else{
            $endpointDef = $this->getBasicDefinition(Endpoint::class);
            $endpointDef->addMethodCall('setURI',array($uri));
            return $this->registerEndpoint($endpointDef, $id, $uri);
        }
    }

    /**
     * @return Reference
     */
    public function buildItinerary($name = null)
    {
        if(!$name){
            $name = 'itinerary.'.$this->getNextIncrementalId();
        }

        $itineraryClass = $this->container->getParameter('smartesb.itinerary.class');
        $def = $this->getBasicDefinition($itineraryClass);
        $def->setArguments(array($name));

        return $this->registerService($def, 'itinerary');
    }

    /**
     * @param Reference $itinerary
     * @param Reference $processor
     */
    public function addToItinerary(Reference $itinerary, Reference $processor)
    {
        $itineraryDef = $this->container->getDefinition($itinerary);
        $itineraryDef->addMethodCall('addProcessor', array($processor));
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

//    /**
//     * TODO: CHECK WHERE DOES THIS GOES
//     * Validates the option for a specific endpoint
//     *
//     * @param $uri
//     * @param $serviceId
//     */
//    private function validateEndpointOptions($uri, $serviceId)
//    {
//        $query = parse_url($uri, PHP_URL_QUERY);
//        if ($query) {
//            $options = array();
//            parse_str($query, $options);
//
//
//            $className = $this->container->findDefinition($serviceId)->getClass();
//            $candidateClassNameParam = str_replace('%', '', $className);
//            if (!class_exists($className) && $this->container->hasParameter($candidateClassNameParam)) {
//
//                $className = $this->container->getParameter($candidateClassNameParam);
//
//                if (!class_exists($className)) {
//                    throw new InvalidConfigurationException(
//                        sprintf('Class "%s" is missing in service "%s"', $className, $serviceId)
//                    );
//                }
//            }
//            call_user_func([$className, 'validateOptions'], $options);
//        }
//    }
}