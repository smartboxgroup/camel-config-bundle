<?php

namespace Smartbox\Integration\CamelConfigBundle\Command;

use Smartbox\Integration\CamelConfigBundle\DependencyInjection\FlowsBuilderCompilerPass;
use Smartbox\Integration\CamelConfigBundle\ProcessorDefinitions\Registry\ProcessorDefinitionsRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FreezeFlowsCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /** @var ProcessorDefinitionsRegistry */
    protected $processorDefinitionsRegistry;

    protected $processorsNodeNames = [];

    protected $rebuiltNodesCounter = 0;

    protected function configure()
    {
        $this
            ->setName('smartesb:flows:freeze')
            ->setDescription('Freezes the current flows in %smartesb.flows_directories% copying them to the %smartesb.frozen_flows_directory%/%smartesb.flows_version%')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $container = $this->getContainer();
        $this->processorDefinitionsRegistry = $container->get('smartesb.registry.processor_definitions');

        $this->processorsNodeNames = $this->processorDefinitionsRegistry->getRegisteredDefinitionsNodeNames();
        $this->processorsNodeNames = array_merge(
            $this->processorsNodeNames,
            [FlowsBuilderCompilerPass::FROM, FlowsBuilderCompilerPass::TO, FlowsBuilderCompilerPass::ROUTE]
        );

        $flowsDirectories = $container->getParameter('smartesb.flows_directories');
        $frozenFlowsDir = $container->getParameter('smartesb.frozen_flows_directory');
        $version = $container->getParameter('smartesb.flows_version');

        if(!file_exists($frozenFlowsDir) || !is_dir($frozenFlowsDir)){
            throw new \Exception("The frozen flows directories path ($frozenFlowsDir) doesn't exists or is not a directory");
        }

        $versionDir = realpath($frozenFlowsDir).'/'.$version.'/';

        $output->writeln("");
        if(!file_exists($versionDir)){
            mkdir($versionDir);
        }

        foreach($flowsDirectories as $dir){
            $dir = realpath($dir);
            $output->writeln("<info>Copying flows from $dir to $versionDir</info>");

            if(!file_exists($dir) || !is_dir($dir)){
                throw new \Exception("The flows directories path ($dir) doesn't exists or is not a directory");
            }

            $this->recurse_copy($dir,$versionDir.basename($dir));
        }
        $output->writeln("");

        $this->rebuildFlows($versionDir);
    }

    private function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                } else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function rebuildFlows($dir)
    {
        $finder = new Finder();
        /** @var SplFileInfo[] $files */
        $files = $finder->files()->name('*.xml')->in($dir);

        foreach($files as $file) {
            $this->rebuiltNodesCounter = 0;
            $idPrefix = str_replace(DIRECTORY_SEPARATOR, '.', $file->getRelativePathname());

            $nodeConfig = new \SimpleXMLElement($file->getContents());
            $this->rebuildNode($nodeConfig, $idPrefix);
            $nodeConfig->saveXML($file->getRealPath());
        }
    }

    /**
     * This method takes object and modifies it
     *
     * @param \SimpleXMLElement $nodeConfig
     * @param $idPrefix
     */
    private function rebuildNode(\SimpleXMLElement $nodeConfig, $idPrefix)
    {
        /** @var \SimpleXMLElement $node */
        foreach ($nodeConfig as $nodeName => $node) {
            $nodeId = (string) @$node['id'];
            if (in_array($nodeName, $this->processorsNodeNames) && ! $nodeId) {
                $node->addAttribute('id', $idPrefix . '.' . $this->rebuiltNodesCounter++);
            }

            $this->rebuildNode($node, $idPrefix);
        }
    }
}