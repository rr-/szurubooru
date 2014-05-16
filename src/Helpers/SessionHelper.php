<?php
class SessionHelper
{
	public static function init()
	{
		session_start();
		register_shutdown_function(function()
		{
			if (\Chibi\Util\Headers::getRequestMethod() == 'GET')
				$_SESSION['last-visited-url'] = Core::getContext()->query;
		});
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

	public static function getLastVisitedUrl()
	{
		if (!isset($_SESSION['last-visited-url']))
			return '';
		return \Chibi\Util\Url::makeAbsolute($_SESSION['last-visited-url']);
	}
}
