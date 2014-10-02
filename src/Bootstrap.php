<?php
namespace Szurubooru;

final class Bootstrap
{
	public static function init()
	{
		self::turnErrorsIntoExceptions();
		self::initAutoloader();
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

Bootstrap::init();
