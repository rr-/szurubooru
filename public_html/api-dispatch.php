<?php
$start = microtime(true);

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'AutoLoader.php');
$dispatcher = \Szurubooru\Injector::get(\Szurubooru\Dispatcher::class);
$dispatcher->run();
