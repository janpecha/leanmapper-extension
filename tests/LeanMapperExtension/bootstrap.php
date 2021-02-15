<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/classes.php';

Tester\Environment::setup();

// create temporary directory
define('TEMP_DIR', __DIR__ . '/../tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
@mkdir(TEMP_DIR, 0777, TRUE);
Tester\Helpers::purge(TEMP_DIR);


// helpers
function test($fnc)
{
	$fnc();
}


/**
 * @param string $configFile
 * @return \SystemContainer|Nette\DI\Container
 */
function createContainer($configFile)
{
	$config = new Nette\Configurator();
	$config->setTempDirectory(TEMP_DIR);
	$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
	$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');
	JP\LeanMapperExtension\LeanMapperExtension::register($config);

	return $config->createContainer();
}
