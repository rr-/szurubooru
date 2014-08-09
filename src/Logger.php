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
		TransferHelper::createDirectory(dirname(self::$path));
		#if (!is_writable(self::$path))
		#	throw new SimpleException('Cannot write logs to "' . self::$path . '". Check access rights.');
	}

	public static function bufferChanges()
	{
		self::$autoFlush = false;
	}

	public static function flush()
	{
		if (empty(self::$buffer))
			return;

		$sep = file_exists(self::$path)
			? PHP_EOL
			: '';
		$fh = fopen(self::$path, 'ab');
		if (!$fh)
			throw new SimpleException('Cannot write to log files');
		if (flock($fh, LOCK_EX))
		{
			foreach (self::$buffer as $logEvent)
			{
				fwrite($fh, $sep . $logEvent->getFullText());
				$sep = PHP_EOL;
			}
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
			TextHelper::replaceTokens(Core::getConfig()->main->logsPath, [
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

	public static function getBuffer()
	{
		return self::$buffer;
	}

	public static function discardBuffer()
	{
		self::$buffer = [];
	}
}
