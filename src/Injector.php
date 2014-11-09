<?php
namespace Szurubooru;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\ArrayCache;

final class Injector
{
	private static $container;
	private static $definitionCache;

	public static function init()
	{
		$definitionsPath = __DIR__
			. DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . 'src'
			. DIRECTORY_SEPARATOR . 'di.php';

		self::$definitionCache = new ArrayCache();
		$builder = new ContainerBuilder();
		$builder->setDefinitionCache(self::$definitionCache);
		$builder->addDefinitions($definitionsPath);
		$builder->useAutowiring(true);
		$builder->useAnnotations(false);
		self::$container = $builder->build();
	}

	public static function get($className)
	{
		return self::$container->get($className);
	}

	public static function set($className, $object)
	{
		self::$container->set($className, $object);
		self::$definitionCache->delete($className);
		self::$definitionCache->flushAll();
	}
}

Injector::init();
