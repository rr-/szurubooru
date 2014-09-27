<?php
namespace Szurubooru;

final class Injector
{
	private static $container;

	public static function init()
	{
		$definitionsPath = __DIR__
			. DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . 'src'
			. DIRECTORY_SEPARATOR . 'di.php';

		$builder = new \DI\ContainerBuilder();
		$builder->setDefinitionCache(new \Doctrine\Common\Cache\ArrayCache());
		$builder->addDefinitions($definitionsPath);
		self::$container = $builder->build();
	}

	public static function get($className)
	{
		return self::$container->get($className);
	}

	public static function set($className, $object)
	{
		return self::$container->set($className, $object);
	}
}

Injector::init();
