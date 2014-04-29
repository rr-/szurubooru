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

//load config manually
$configPaths =
[
	$rootDir . DS . 'data' . DS . 'config.ini',
	$rootDir . DS . 'data' . DS . 'local.ini',
];
$config = new \Chibi\Config();
foreach ($configPaths as $path)
	if (file_exists($path))
		$config->loadIni($path);
$config->rootDir = $rootDir;

function getConfig()
{
	global $config;
	return $config;
}


//extension sanity checks
$requiredExtensions = ['pdo', 'pdo_' . $config->main->dbDriver, 'gd', 'openssl', 'fileinfo'];
foreach ($requiredExtensions as $ext)
	if (!extension_loaded($ext))
		die('PHP extension "' . $ext . '" must be enabled to continue.' . PHP_EOL);

\Chibi\Database::connect(
	$config->main->dbDriver,
	TextHelper::absolutePath($config->main->dbLocation),
	$config->main->dbUser,
	$config->main->dbPass);

//wire models
foreach (\Chibi\AutoLoader::getAllIncludablePaths() as $path)
	if (preg_match('/Model/', $path))
		\Chibi\AutoLoader::safeInclude($path);
