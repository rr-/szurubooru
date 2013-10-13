<?php
function trueStartTime()
{
	static $time = null;
	if ($time === null)
		$time = microtime(true);
	return $time;
}
trueStartTime();

require_once 'lib/redbean/RedBean/redbean.inc.php';
require_once 'lib/chibi-core/Facade.php';

date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');
define('DS', DIRECTORY_SEPARATOR);

function configFactory()
{
	static $config = null;

	if ($config === null)
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

	}
	return $config;
}

$config = configFactory();
R::setup('sqlite:' . $config->main->dbPath);
#R::dependencies(['tag' => ['post'], 'post' => ['user']]);

//wire models
\Chibi\AutoLoader::init([$config->chibi->userCodeDir, __DIR__]);
foreach (\Chibi\AutoLoader::getAllIncludablePaths() as $path)
	if (preg_match('/Model/', $path))
		\Chibi\AutoLoader::safeInclude($path);
