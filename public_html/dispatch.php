<?php
chdir('..');
require_once 'redbean/RedBean/redbean.inc.php';
require_once 'chibi-core/Facade.php';

date_default_timezone_set('UTC');
setlocale(LC_CTYPE, 'en_US.UTF-8');

class Bootstrap
{
	public function workWrapper($workCallback)
	{
		$this->config->chibi->baseUrl = 'http://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/';
		R::setup('sqlite:' . $this->config->main->dbPath);
		$workCallback();
	}
}

$query = $_SERVER['REQUEST_URI'];
$configPaths =
[
	__DIR__ . DIRECTORY_SEPARATOR . '../config.ini',
	__DIR__ . DIRECTORY_SEPARATOR . '../local.ini'
];
$configPaths = array_filter($configPaths, 'file_exists');

\Chibi\Facade::run($query, $configPaths, new Bootstrap());
