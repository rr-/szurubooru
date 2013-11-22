<?php
class LogHelper
{
	static $path;
	static $context;
	static $config;
	static $autoFlush;
	static $content;

	public static function init()
	{
		self::$config = \Chibi\Registry::getConfig();
		self::$context = \Chibi\Registry::getContext();
		self::$path = self::$config->main->logsPath . date('Y-m') . '.log';
		self::$autoFlush = true;

		self::$content = '';
	}

	public static function bufferChanges()
	{
		self::$autoFlush = false;
	}

	public static function flush()
	{
		$fh = fopen(self::getLogPath(), 'ab');
		if (!$fh)
			throw new SimpleException('Cannot write to log files');
		if (flock($fh, LOCK_EX))
		{
			fwrite($fh, self::$content);
			fflush($fh);
			flock($fh, LOCK_UN);
			fclose($fh);
		}
		self::$content = '';
		self::$autoFlush = true;
	}

	public static function getLogPath()
	{
		return self::$path;
	}

	public static function log($text, array $tokens = [])
	{
		$tokens['anon'] = Model_User::getAnonymousName();
		if (self::$context->loggedIn and isset(self::$context->user))
			$tokens['user'] = TextHelper::reprUser(self::$context->user->name);
		else
			$tokens['user'] = $tokens['anon'];

		$text = TextHelper::replaceTokens($text, $tokens);

		$timestamp = date('Y-m-d H:i:s');
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		$line = sprintf('[%s] %s: %s' . PHP_EOL, $timestamp, $ip, $text);

		self::$content .= $line;

		if (self::$autoFlush)
			self::flush();
	}

	public static function logEvent($event, $text, array $tokens = [])
	{
		return self::log(sprintf('[%s] %s', $event, $text), $tokens);
	}
}

LogHelper::init();
