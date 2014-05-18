<?php
class SessionHelper
{
	public static function init()
	{
		session_start();
		register_shutdown_function([__CLASS__, 'tryRememberVisitedUrl']);
	}

	public static function get($key, $default = null)
	{
		if (!isset($_SESSION[$key]))
			return $default;
		return $_SESSION[$key];
	}

	public static function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public static function getLastVisitedUrl($filter = null)
	{
		if (isset($_SESSION['last-visited-url']))
			foreach ($_SESSION['last-visited-url'] as $lastUrl)
				if ($filter === null or stripos($lastUrl, $filter) === false)
					return \Chibi\Util\Url::makeAbsolute($lastUrl);
		return null;
	}

	public static function tryRememberVisitedUrl()
	{
		if (\Chibi\Util\Headers::getRequestMethod() != 'GET')
			return;

		if (\Chibi\Util\Headers::getCode() != 200)
			return;

		if (strpos(\Chibi\Util\Headers::get('Content-Type'), 'text/html') === false)
			return;

		self::rememberVisitedUrl();
		self::removeExcessHistory();
	}

	private static function rememberVisitedUrl()
	{
		if (!isset($_SESSION['last-visited-url']))
			$_SESSION['last-visited-url'] = [];

		array_unshift($_SESSION['last-visited-url'], Core::getContext()->query);
	}

	private static function removeExcessHistory()
	{
		array_splice($_SESSION['last-visited-url'], 3);
	}
}
