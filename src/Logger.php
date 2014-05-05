<?php
class Logger
{
	static $context;
	static $config;
	static $autoFlush;
	static $buffer;
	static $path;

	public static function init()
	{
		self::$autoFlush = true;
		self::$buffer = [];
		self::$path = self::getLogPath();
		$dir = dirname(self::$path);
		if (!is_dir($dir))
			mkdir($dir, 0777, true);
		#if (!is_writable(self::$path))
		#	throw new SimpleException('Cannot write logs to "' . self::$path . '". Check access rights.');
	}

	public static function bufferChanges()
	{
		self::$autoFlush = false;
	}

	public static function flush()
	{
		$fh = fopen(self::$path, 'ab');
		if (!$fh)
			throw new SimpleException('Cannot write to log files');
		if (flock($fh, LOCK_EX))
		{
			foreach (self::$buffer as $logEvent)
				fwrite($fh, $logEvent->getFullText() . PHP_EOL);
			fflush($fh);
			flock($fh, LOCK_UN);
			fclose($fh);
		}
		self::$buffer = [];
		self::$autoFlush = true;
	}

	public static function getLogPath()
	{
		return TextHelper::absolutePath(
			TextHelper::replaceTokens(getConfig()->main->logsPath, [
				'yyyy' => date('Y'),
				'mm' => date('m'),
				'dd' => date('d')]));
	}

	public static function log($text, array $tokens = [])
	{
		self::$buffer []= new LogEvent($text, $tokens);
		if (self::$autoFlush)
			self::flush();
	}

	//methods for manipulating buffered logs
	public static function getBuffer()
	{
		return self::$buffer;
	}

	public static function setBuffer(array $buffer)
	{
		self::$buffer = $buffer;
	}
}
