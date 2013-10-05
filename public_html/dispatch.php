<?php
chdir('..');
require_once 'lib/redbean/RedBean/redbean.inc.php';
require_once 'lib/chibi-core/Facade.php';
require_once 'src/Bootstrap.php';

date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');

$query = $_SERVER['REQUEST_URI'];
$configPaths =
[
	__DIR__ . DIRECTORY_SEPARATOR . '../config.ini',
	__DIR__ . DIRECTORY_SEPARATOR . '../local.ini'
];
$configPaths = array_filter($configPaths, 'file_exists');

\Chibi\Facade::run($query, $configPaths, new Bootstrap());
