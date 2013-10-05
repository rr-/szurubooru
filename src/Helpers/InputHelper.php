<?php
class InputHelper
{
	public static function get($keyName)
	{
		if (isset($_POST[$keyName]))
		{
			return $_POST[$keyName];
		}

		if (isset($_GET[$keyName]))
		{
			return $_GET[$keyName];
		}

		if (isset($_COOKIE[$keyName]))
		{
			return $_COOKIE[$keyName];
		}

		return null;
	}
}
