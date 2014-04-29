<?php
class LogHelper
{
	static $context;
	static $config;
	static $autoFlush;
	static $buffer;

	public static function init()
	{
		self::$autoFlush = true;
		self::$buffer = [];
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
		return TextHelper::absolutePath(getConfig()->main->logsPath . DS . date('Y-m') . '.log');
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

class LogEvent
{
	public $timestamp;
	public $text;
	public $ip;
	public $tokens;

	public function __construct($text, array $tokens = [])
	{
		$this->timestamp = time();
		$this->text = $text;
		$this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

		$context = getContext();
		$tokens['anon'] = UserModel::getAnonymousName();
		if ($context->loggedIn and isset($context->user))
			$tokens['user'] = TextHelper::reprUser($context->user->name);
		else
			$tokens['user'] = $tokens['anon'];
		$this->tokens = $tokens;
	}

	public function getText()
	{
		return TextHelper::replaceTokens($this->text, $this->tokens);
	}

	public function getFullText()
	{
		$date = date('Y-m-d H:i:s', $this->timestamp);
		$ip = $this->ip;
		$text = $this->getText();
		$line = sprintf('[%s] %s: %s', $date, $ip, $text);
		return $line;
	}
}

LogHelper::init();
