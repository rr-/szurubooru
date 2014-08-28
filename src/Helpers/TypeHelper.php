<?php
namespace Szurubooru\Helpers;

final class TypeHelper
{
	public static function arrayToClass($array, $className)
	{
		if (!$array)
			return null;

		if (!class_exists($className, true))
			return null;

		return unserialize(
			preg_replace(
				'/^O:[0-9]+:"[^"]+":/i',
				'O:' . strlen($className) . ':"'  . $className . '":',
				serialize((object) $array)));
	}
}
