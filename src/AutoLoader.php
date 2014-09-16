<?php
namespace Szurubooru;

final class AutoLoader
{
	public static function init()
	{
		spl_autoload_register([__CLASS__, 'includeClassName']);
	}

	public static function includeClassName($className)
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
		include($className);
	}
}

AutoLoader::init();

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

function turnErrorsIntoExceptions()
{
	set_error_handler(
		function($errno, $errstr, $errfile, $errline, array $errcontext)
		{
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
}

turnErrorsIntoExceptions();
