<?php
namespace Szurubooru;

final class AutoLoader
{
	public static function init()
	{
		spl_autoload_register([__CLASS__, '_include']);
	}

	public static function _include($className)
	{
		if (strpos($className, 'Szurubooru') === false)
			return;
		$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		$className = str_replace('Szurubooru', '', $className);
		if (strpos($className, 'Tests') !== false)
			$className = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('Tests', 'tests', $className);
		else
			$className = __DIR__ . DIRECTORY_SEPARATOR . $className;
		$className .= '.php';
		include $className;
	}
}

AutoLoader::init();
