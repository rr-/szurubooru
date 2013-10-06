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

	public static function resolveConstant($constantName, $className = null)
	{
		//convert from kebab-case to CamelCase
		$constantName = preg_split('/-/', $constantName);
		$constantName = array_map('trim', $constantName);
		$constantName = array_map('ucfirst', $constantName);
		$constantName = join('', $constantName);
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
