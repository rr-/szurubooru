<?php
class BenchmarkHelper
{
	protected static $lastTime;

	public static function init()
	{
		self::$lastTime = microtime(true);
	}

	public static function tick()
	{
		$t = microtime(true);
		$lt = self::$lastTime;
		self::$lastTime = $t;
		return $t - $lt;
	}
}

BenchmarkHelper::init();
