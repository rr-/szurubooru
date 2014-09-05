<?php
namespace Szurubooru;

final class Injector
{
	private static $container;

	public static function init()
	{
		$builder = new \DI\ContainerBuilder();
		$builder->setDefinitionCache(new \Doctrine\Common\Cache\ArrayCache());
		$builder->addDefinitions(__DIR__ . DS . '..' . DS . 'src' . DS . 'di.php');
		self::$container = $builder->build();
	}

	public static function get($className)
	{
		return self::$container->get($className);
	}
}

Injector::init();
