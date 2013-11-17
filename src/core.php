<?php
define('SZURU_VERSION', '0.3.0');
define('SZURU_LINK', 'http://github.com/rr-/szurubooru');

$startTime = microtime(true);

$requiredExtensions = ['pdo', 'pdo_sqlite', 'gd', 'openssl'];
foreach ($requiredExtensions as $ext)
	if (!extension_loaded($ext))
		die('PHP extension "' . $ext . '" must be enabled to continue.' . PHP_EOL);

date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');
ini_set('memory_limit', '128M');
define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . '/../lib/php-markdown/Michelf/Markdown.php';
require_once __DIR__ . '/../lib/redbean/RedBean/redbean.inc.php';
require_once __DIR__ . '/../lib/chibi-core/Facade.php';

\Chibi\AutoLoader::init(__DIR__);
\Chibi\Facade::init();
$config = \Chibi\Registry::getConfig();
$context = \Chibi\Registry::getContext();
$context->startTime = $startTime;

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
