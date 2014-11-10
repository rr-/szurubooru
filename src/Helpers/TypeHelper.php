<?php
namespace Szurubooru\Helpers;

class TypeHelper
{
	public static function toBool($value)
	{
		if ($value === 0)
			return false;
		if ($value === 'false')
			return false;
		if ($value === false)
			return false;
		if (is_array($value) && count($value) === 1)
			return false;
		if ($value === null)
			return false;
		return true;
	}
}
