<?php
require_once 'lib/redbean/RedBean/redbean.inc.php';
require_once 'lib/chibi-core/Facade.php';

date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');
define('DS', DIRECTORY_SEPARATOR);

function configFactory()
{
	$config = new \Chibi\Config();
	$configPaths =
	[
		__DIR__ . DS . '../config.ini',
		__DIR__ . DS . '../local.ini'
	];
	$configPaths = array_filter($configPaths, 'file_exists');

	foreach ($configPaths as $path)
	{
		$config->loadIni($path);
	}

	return $config;
}
