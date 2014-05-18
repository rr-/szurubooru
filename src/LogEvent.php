<?php
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
