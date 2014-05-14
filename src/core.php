<?php
$startTime = microtime(true);

//basic settings and preparation
define('DS', DIRECTORY_SEPARATOR);
$rootDir = __DIR__ . DS . '..' . DS;
chdir($rootDir);
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

require_once $rootDir . 'src' . DS . 'routes.php';

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

function prepareConfig($testEnvironment)
{
	//load config manually
	global $config;
	global $rootDir;

	$configPaths = [];
	if (!$testEnvironment)
	{
		$configPaths []= $rootDir . DS . 'data' . DS . 'config.ini';
		$configPaths []= $rootDir . DS . 'data' . DS . 'local.ini';
	}
	else
	{
		$configPaths []= $rootDir . DS . 'tests' . DS . 'config.ini';
	}

	$config = new \Chibi\Config();
	foreach ($configPaths as $path)
		if (file_exists($path))
			$config->loadIni($path);
	$config->rootDir = $rootDir;
}

function prepareEnvironment($testEnvironment)
{
	//prepare context
	global $context;
	global $startTime;
	$context = new StdClass;
	$context->startTime = $startTime;

	$config = getConfig();

	TransferHelper::createDirectory($config->main->filesPath);
	TransferHelper::createDirectory($config->main->thumbsPath);

	//extension sanity checks
	$requiredExtensions = ['pdo', 'pdo_' . $config->main->dbDriver, 'gd', 'openssl', 'fileinfo'];
	foreach ($requiredExtensions as $ext)
		if (!extension_loaded($ext))
			die('PHP extension "' . $ext . '" must be enabled to continue.' . PHP_EOL);

	if (\Chibi\Database::connected())
		\Chibi\Database::disconnect();

	if ($testEnvironment)
		Auth::setCurrentUser(null);
	Access::init();
	Logger::init();
	Mailer::init();
	PropertyModel::init();

	\Chibi\Database::connect(
		$config->main->dbDriver,
		TextHelper::absolutePath($config->main->dbLocation),
		isset($config->main->dbUser) ? $config->main->dbUser : null,
		isset($config->main->dbPass) ? $config->main->dbPass : null);
}

prepareConfig(false);
prepareEnvironment(false);
