<?php
$start = microtime(true);

define('DS', DIRECTORY_SEPARATOR);
require_once(__DIR__ . DS . '..' . DS . 'vendor' . DS . 'autoload.php');
require_once(__DIR__ . DS . '..' . DS . 'src' . DS . 'AutoLoader.php');

$builder = new \DI\ContainerBuilder();
$builder->setDefinitionCache(new Doctrine\Common\Cache\ArrayCache());
$builder->addDefinitions(__DIR__ . DS . '..' . DS . 'src' . DS . 'di.php');
$container = $builder->build();
$dispatcher = $container->get('Szurubooru\Dispatcher');
$dispatcher->run();
