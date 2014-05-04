<?php
$startTime = microtime(true);

define('SZURU_VERSION', '0.7.1');
define('SZURU_LINK', 'http://github.com/rr-/szurubooru');

//basic settings and preparation
define('DS', DIRECTORY_SEPARATOR);
$rootDir = __DIR__ . DS . '..' . DS;
date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');
ini_set('memory_limit', '128M');

//basic include calls, autoloader init
require_once $rootDir . 'lib' . DS . 'TextCaseConverter' . DS . 'TextCaseConverter.php';
require_once $rootDir . 'lib' . DS . 'php-markdown' . DS . 'Michelf' . DS . 'Markdown.php';
require_once $rootDir . 'lib' . DS . 'php-markdown' . DS . 'Michelf' . DS . 'MarkdownExtra.php';
require_once $rootDir . 'lib' . DS . 'chibi-core' . DS . 'include.php';
\Chibi\AutoLoader::registerFilesystem($rootDir . 'lib' . DS . 'chibi-sql');
\Chibi\AutoLoader::registerFilesystem(__DIR__);

function getConfig()
{
	global $config;
	return $config;
}

function getContext()
{
	global $context;
	return $context;
}

function resetEnvironment()
{
	//load config manually
	global $config;
	global $rootDir;
	global $startTime;

	$configPaths =
	[
		$rootDir . DS . 'data' . DS . 'config.ini',
		$rootDir . DS . 'data' . DS . 'local.ini',
		$rootDir . DS . 'tests' . DS . 'test.ini',
	];
	$config = new \Chibi\Config();
	foreach ($configPaths as $path)
		if (file_exists($path))
			$config->loadIni($path);
	$config->rootDir = $rootDir;

	//prepare context
	global $context;
	$context = new StdClass;
	$context->startTime = $startTime;

	//extension sanity checks
	$requiredExtensions = ['pdo', 'pdo_' . $config->main->dbDriver, 'gd', 'openssl', 'fileinfo'];
	foreach ($requiredExtensions as $ext)
		if (!extension_loaded($ext))
			die('PHP extension "' . $ext . '" must be enabled to continue.' . PHP_EOL);

	if (\Chibi\Database::connected())
		\Chibi\Database::disconnect();

	Auth::setCurrentUser(null);
	Access::init();
	Logger::init();

	\Chibi\Database::connect(
		$config->main->dbDriver,
		TextHelper::absolutePath($config->main->dbLocation),
		$config->main->dbUser,
		$config->main->dbPass);
}

resetEnvironment();
