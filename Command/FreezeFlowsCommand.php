<?php

namespace Smartbox\Integration\CamelConfigBundle\Command;

use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class FreezeFlowsCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

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
    }

    private function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}