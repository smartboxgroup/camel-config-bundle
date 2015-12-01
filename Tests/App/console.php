<?php
// File: Tests/app/console.php
use \Smartbox\Integration\CamelConfigBundle\Tests\App\AppKernel;

set_time_limit(0);

require_once __DIR__.'/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('dev', true);
$application = new Application($kernel);
$application->run();