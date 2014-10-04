<?php
namespace Szurubooru;

$startTime = microtime(true);

final class Bootstrap
{
	private static $startTime;

	public static function init($startTime)
	{
		self::$startTime = $startTime;
		self::turnErrorsIntoExceptions();
		self::initAutoloader();
	}

	public static function getStartTime()
	{
		return self::$startTime;
	}

	private static function initAutoloader()
	{
		require(__DIR__
			. DIRECTORY_SEPARATOR . '..'
			. DIRECTORY_SEPARATOR . 'vendor'
			. DIRECTORY_SEPARATOR . 'autoload.php');
	}

	private static function turnErrorsIntoExceptions()
	{
		set_error_handler(
			function($errno, $errstr, $errfile, $errline, array $errcontext)
			{
				throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
			});
	}
}

Bootstrap::init($startTime);
