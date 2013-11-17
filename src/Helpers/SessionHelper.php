<?php
class SessionHelper
{
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
}
