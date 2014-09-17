<?php
$start = microtime(true);

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'AutoLoader.php');
$httpHelper = \Szurubooru\Injector::get(\Szurubooru\Helpers\HttpHelper::class);
$dispatcher = \Szurubooru\Injector::get(\Szurubooru\Dispatcher::class);
$dispatcher->run($httpHelper->getRequestMethod(), $_GET['q']);
