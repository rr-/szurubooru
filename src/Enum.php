<?php
class Enum
{
	public static function toString($constant)
	{
		$cls = new ReflectionClass(get_called_class());
		$constants = $cls->getConstants();
		return array_search($constant, $constants);
	}
}
