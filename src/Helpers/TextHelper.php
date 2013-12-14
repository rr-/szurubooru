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

	public static function camelCaseToHumanCase($string, $ucfirst = false)
	{
		$string = preg_replace_callback('/[A-Z]/', function($x)
		{
			return ' ' . strtolower($x[0]);
		}, $string);
		$string = trim($string);
		if ($ucfirst)
			$string = ucfirst($string);
		return $string;
	}

	public static function humanCaseToKebabCase($string)
	{
		$string = trim($string);
		$string = str_replace(' ', '-', $string);
		$string = strtolower($string);
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

	private static function stripUnits($string, $base, $suffixes)
	{
		$suffix = substr($string, -1, 1);
		$index = array_search($suffix, $suffixes);
		if ($index === false)
			return $string;
		$number = intval($string);
		for ($i = 0; $i < $index; $i ++)
			$number *= $base;
		return $number;
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

	public static function stripBytesUnits($string)
	{
		return self::stripUnits($string, 1024, ['B', 'K', 'M', 'G']);
	}

	public static function stripDecimalUnits($string)
	{
		return self::stripUnits($string, 1000, ['', 'K', 'M']);
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
			$set = function($key, $val) use ($obj) { $obj[$key] = $val; };
		else
			$set = function($key, $val) use ($obj) { $obj->$key = $val; };

		foreach ($obj as $key => $val)
		{
			if ($val instanceof RedBean_OODBBean)
			{
				$set($key, R::exportAll($val));
			}
			elseif ($val instanceof Exception)
			{
				$set($key, ['message' => $val->getMessage(), 'trace' => $val->getTraceAsString()]);
			}
		}

		if (!empty($illegalKeysRegex))
			self::removeUnsafeKeys($obj, $illegalKeysRegex);

		return json_encode($obj, JSON_UNESCAPED_UNICODE);
	}

	public static function parseMarkdown($text, $simple = false)
	{
		if ($simple)
			return CustomMarkdown::simpleTransform($text);
		else
			return CustomMarkdown::defaultTransform($text);
	}

	public static function reprPost($post)
	{
		if (!is_object($post))
			return '@' . $post;
		return '@' . $post->id;
	}

	public static function reprUser($user)
	{
		if (!is_object($user))
			return '+' . $user;
		return '+' . $user->name;
	}

	public static function reprTag($tag)
	{
		if (!is_object($tag))
			return '#' . $tag;
		return '#' . $tag->name;
	}

	public static function reprTags($tags)
	{
		$x = [];
		foreach ($tags as $tag)
			$x []= self::reprTag($tag);
		natcasesort($x);
		return join(', ', $x);
	}

	public static function encrypt($text)
	{
		$salt = \Chibi\Registry::getConfig()->main->salt;
		$alg = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_ECB;
		$iv = mcrypt_create_iv(mcrypt_get_iv_size($alg, $mode), MCRYPT_RAND);
		return trim(base64_encode(mcrypt_encrypt($alg, $salt, $text, $mode, $iv)));
	}

	public static function decrypt($text)
	{
		$salt = \Chibi\Registry::getConfig()->main->salt;
		$alg = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_ECB;
		$iv = mcrypt_create_iv(mcrypt_get_iv_size($alg, $mode), MCRYPT_RAND);
		return trim(mcrypt_decrypt($alg, $salt, base64_decode($text), $mode, $iv));
	}

	public static function cleanPath($path)
	{
		$path = str_replace(['/', '\\'], DS, $path);
		$path = preg_replace('{[^' . DS . ']+' . DS . '\.\.(' . DS . '|$)}', '', $path);
		$path = preg_replace('{(' . DS . '|^)\.' . DS . '}', '\1', $path);
		$path = preg_replace('{' . DS . '{2,}}', DS, $path);
		$path = rtrim($path, DS);
		return $path;
	}

	public static function absolutePath($path)
	{
		if ($path{0} != DS)
			$path = \Chibi\Registry::getContext()->rootDir . DS . $path;

		$path = self::cleanPath($path);
		return $path;
	}

	const HTML_OPEN = 1;
	const HTML_CLOSE = 2;
	const HTML_LEAF = 3;

	public static function htmlTag($tagName, $tagStyle, array $attributes = [])
	{
		$html = '<';
		if ($tagStyle == self::HTML_CLOSE)
			$html .= '/';

		$html .= $tagName;

		if ($tagStyle == self::HTML_OPEN or $tagStyle == self::HTML_LEAF)
		{
			foreach ($attributes as $key => $value)
			{
				$html .= ' ' . $key . '="' . $value . '"';
			}
		}

		if ($tagStyle == self::HTML_LEAF)
			$html .= '/';
		$html .= '>';

		return $html;
	}
}
