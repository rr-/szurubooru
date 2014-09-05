<?php
$start = microtime(true);

define('DS', DIRECTORY_SEPARATOR);
require_once(__DIR__ . DS . '..' . DS . 'vendor' . DS . 'autoload.php');
require_once(__DIR__ . DS . '..' . DS . 'src' . DS . 'AutoLoader.php');

$dispatcher = \Szurubooru\Injector::get(\Szurubooru\Dispatcher::class);
$dispatcher->run();
