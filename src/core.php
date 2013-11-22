<?php
define('SZURU_VERSION', '0.4.1');
define('SZURU_LINK', 'http://github.com/rr-/szurubooru');

//basic settings and preparation
define('DS', DIRECTORY_SEPARATOR);
$startTime = microtime(true);
$rootDir = __DIR__ . DS . '..' . DS;
date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');
ini_set('memory_limit', '128M');

//extension sanity checks
$requiredExtensions = ['pdo', 'pdo_sqlite', 'gd', 'openssl', 'fileinfo'];
foreach ($requiredExtensions as $ext)
	if (!extension_loaded($ext))
		die('PHP extension "' . $ext . '" must be enabled to continue.' . PHP_EOL);

//basic include calls, autoloader init
require_once $rootDir . 'lib' . DS . 'php-markdown' . DS . 'Michelf' . DS . 'Markdown.php';
require_once $rootDir . 'lib' . DS . 'redbean' . DS . 'RedBean' . DS . 'redbean.inc.php';
require_once $rootDir . 'lib' . DS . 'chibi-core' . DS . 'Facade.php';
\Chibi\AutoLoader::init(__DIR__);

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
\Chibi\Registry::setConfig($config);

//prepare context
\Chibi\Facade::init();
$context = \Chibi\Registry::getContext();
$context->startTime = $startTime;
$context->rootDir = $rootDir;

//load database
R::setup('sqlite:' . $config->main->dbPath);
R::freeze(true);
R::dependencies(['tag' => ['post'], 'favoritee' => ['post', 'user'], 'comment' => ['post', 'user']]);

//wire models
foreach (\Chibi\AutoLoader::getAllIncludablePaths() as $path)
	if (preg_match('/Model/', $path))
		\Chibi\AutoLoader::safeInclude($path);

function queryLogger()
{
	static $queryLogger = null;
	if ($queryLogger === null)
		$queryLogger = RedBean_Plugin_QueryLogger::getInstanceAndAttach(R::getDatabaseAdapter());
	return $queryLogger;
}
queryLogger();
