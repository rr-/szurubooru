<?php
class TextHelper
{
	public static function isValidEmail($email)
	{
		$emailRegex = '/^[^@]+@[^@]+\.[^@]+$/';
		return preg_match($emailRegex, $email);
	}

	public static function replaceTokens($text, array $tokens)
	{
		foreach ($tokens as $key => $value)
		{
			$token = '{' . $key . '}';
			$text = str_replace($token, $value, $text);
		}
		return $text;
	}

	public static function kebabCaseToCamelCase($string)
	{
		$string = preg_split('/-/', $string);
		$string = array_map('trim', $string);
		$string = array_map('ucfirst', $string);
		$string = join('', $string);
		return $string;
	}

	public static function camelCaseToKebabCase($string)
	{
		$string = preg_replace_callback('/[A-Z]/', function($x)
		{
			return '-' . strtolower($x[0]);
		}, $string);
		$string = trim($string, '-');
		return $string;
	}

	public static function resolveConstant($constantName, $className = null)
	{
		$constantName = self::kebabCaseToCamelCase($constantName);
		//convert from kebab-case to CamelCase
		if ($className !== null)
		{
			$constantName = $className . '::' . $constantName;
		}
		if (!defined($constantName))
		{
			throw new Exception('Undefined constant: ' . $constantName);
		}
		return constant($constantName);
	}
}
