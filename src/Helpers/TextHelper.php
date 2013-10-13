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

	private static function useUnits($number, $base, $suffixes)
	{
		$suffix = array_shift($suffixes);
		if ($number < $base)
		{
			return sprintf('%d%s', $number, $suffix);
		}
		do
		{
			$suffix = array_shift($suffixes);
			$number /= (float) $base;
		}
		while ($number >= $base and !empty($suffixes));
		return sprintf('%.01f%s', $number, $suffix);
	}

	public static function useBytesUnits($number)
	{
		return self::useUnits($number, 1024, ['B', 'K', 'M', 'G']);
	}

	public static function useDecimalUnits($number)
	{
		return self::useUnits($number, 1000, ['', 'K', 'M']);
	}

	public static function removeUnsafeKeys(&$input, $regex)
	{
		if (is_array($input))
		{
			foreach ($input as $key => $val)
			{
				if (preg_match($regex, $key))
					unset($input[$key]);
				else
					self::removeUnsafeKeys($input[$key], $regex);
			}
		}
		elseif (is_object($input))
		{
			foreach ($input as $key => $val)
			{
				if (preg_match($regex, $key))
					unset($input->$key);
				else
					self::removeUnsafeKeys($input->$key, $regex);
			}
		}
	}

	public static function jsonEncode($obj, $illegalKeysRegex = '')
	{
		if (is_array($obj))
		{
			foreach ($obj as $key => $val)
			{
				if ($val instanceof RedBean_OODBBean)
				{
					$obj[$key] = R::exportAll($val);
				}
			}
		}
		elseif (is_object($obj))
		{
			foreach ($obj as $key => $val)
			{
				if ($val instanceof RedBean_OODBBean)
				{
					$obj->$key = R::exportAll($val);
				}
			}
		}

		if (!empty($illegalKeysRegex))
			self::removeUnsafeKeys($obj, $illegalKeysRegex);

		return json_encode($obj, true);
	}
}
