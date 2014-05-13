<?php
class TextHelper
{
	public static function isValidEmail($email)
	{
		$emailRegex = '/^[^@]+@[^@]+\.[^@]+$/';
		return preg_match($emailRegex, $email);
	}

	public static function toIntegerOrNull($x)
	{
		if ($x === true or $x === false)
			return null;

		if ($x === 0 or $x === '0')
			return 0;

		$y = intval($x);

		if ($y !== 0)
		{
			if (!preg_match('/^-?\d+$/', $x))
				return null;

			return $y;
		}

		return null;
	}

	public static function toBooleanOrNull($x)
	{
		switch (strtolower($x))
		{
			case '1':
			case 'true':
			case 'on':
			case 'yes':
			case 'y':
				return true;
			case '0':
			case 'false':
			case 'off':
			case 'no':
			case 'n':
				return false;
			default:
				return null;
		}
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
		$constantName = TextCaseConverter::convert($constantName,
			TextCaseConverter::SPINAL_CASE,
			TextCaseConverter::CAMEL_CASE);

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
		return floatval($string) * pow($base, $index !== false ? $index : 0);
	}

	private static function useUnits($number, $base, $suffixes, $fmtCallback = null)
	{
		$suffix = array_shift($suffixes);

		while ($number >= $base and !empty($suffixes))
		{
			$suffix = array_shift($suffixes);
			$number /= (float) $base;
		}

		if ($fmtCallback === null)
		{
			$fmtCallback = function($number, $suffix)
			{
				if ($suffix == '')
					return $number;
				return sprintf('%.01f%s', $number, $suffix);
			};
		}

		return $fmtCallback($number, $suffix);
	}

	public static function useBytesUnits($number)
	{
		return self::useUnits(
			$number,
			1024,
			['B', 'K', 'M', 'G'],
			function($number, $suffix)
			{
				if ($number < 20 and $suffix != 'B')
					return sprintf('%.01f%s', $number, $suffix);
				return sprintf('%.0f%s', $number, $suffix);
			});
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
			if ($val instanceof Exception)
			{
				$set($key, ['message' => $val->getMessage(), 'trace' => explode("\n", $val->getTraceAsString())]);
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
		return '@' . $post->getId();
	}

	public static function reprUser($user)
	{
		if (!is_object($user))
			return '+' . $user;
		return '+' . $user->getName();
	}

	public static function reprTag($tag)
	{
		if (!is_object($tag))
			return '#' . $tag;
		return '#' . $tag->getName();
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
		$salt = getConfig()->main->salt;
		$alg = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_CBC;
		$iv = mcrypt_create_iv(mcrypt_get_iv_size($alg, $mode), MCRYPT_RAND);
		return base64_encode($iv) . '|' . base64_encode(mcrypt_encrypt($alg, $salt, $text, $mode, $iv));
	}

	public static function decrypt($text)
	{
		try
		{
			$salt = getConfig()->main->salt;
			list ($iv, $hash) = explode('|', $text, 2);
			$iv = base64_decode($iv);
			$hash = base64_decode($hash);
			$alg = MCRYPT_RIJNDAEL_256;
			$mode = MCRYPT_MODE_CBC;
			$ret = mcrypt_decrypt($alg, $salt, $hash, $mode, $iv);
			$pos = strpos($ret, "\0");
			if ($pos !== false)
				$ret = substr($ret, 0, $pos);
			return $ret;
		}
		catch (Exception $e)
		{
			throw new SimpleException('Supplied input is not valid encrypted text');
		}
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
			$path = getConfig()->rootDir . DS . $path;

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

	public static function formatDate($date, $plain = true)
	{
		if (!$date)
			return 'Unknown';
		if ($plain)
			return date('Y-m-d H:i:s', $date);

		$now = time();
		$diff = abs($now - $date);
		$future = $now < $date;

		$mul = 60;
		if ($diff < $mul)
			return $future ? 'in a few seconds' : 'just now';
		if ($diff < $mul * 2)
			return $future ? 'in a minute' : 'a minute ago';

		$prevMul = $mul; $mul *= 60;
		if ($diff < $mul)
			return $future ? 'in ' . round($diff / $prevMul) . ' minutes' : round($diff / $prevMul) . ' minutes ago';
		if ($diff < $mul * 2)
			return $future ? 'in an hour' : 'an hour ago';

		$prevMul = $mul; $mul *= 24;
		if ($diff < $mul)
			return $future ? 'in ' . round($diff / $prevMul) . ' hours' : round($diff / $prevMul) . ' hours ago';
		if ($diff < $mul * 2)
			return $future ? 'tomorrow' : 'yesterday';

		$prevMul = $mul; $mul *= 30.42;
		if ($diff < $mul)
			return $future ? 'in ' . round($diff / $prevMul) . ' days' : round($diff / $prevMul) . ' days ago';
		if ($diff < $mul * 2)
			return $future ? 'in a month' : 'a month ago';

		$prevMul = $mul; $mul *= 12;
		if ($diff < $mul)
			return $future ? 'in ' . round($diff / $prevMul) . ' months' : round($diff / $prevMul) . ' months ago';
		if ($diff < $mul * 2)
			return $future ? 'in a year' : 'a year ago';

		return $future ? 'in ' . round($diff / $mul) . ' years' : round($diff / $prevMul) . ' years ago';
	}

	public static function resolveMimeType($mimeType)
	{
		$mimeTypes = [
			'image/jpeg' => 'jpg',
			'image/gif' => 'gif',
			'image/png' => 'png',
			'application/x-shockwave-flash' => 'swf',
			'video/mp4' => 'mp4',
			'video/webm' => 'webm',
			'video/ogg' => 'ogg',
			'application/ogg' => 'ogg',
			'video/x-flv' => 'flv',
			'video/3gpp' => '3gp'];
		return isset($mimeTypes[$mimeType])
			? $mimeTypes[$mimeType]
			: null;
	}
}
