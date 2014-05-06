<?php
abstract class Enum
{
	public abstract function toString();

	public function _toString($constant)
	{
		$cls = new ReflectionClass(get_called_class());
		$constants = $cls->getConstants();
		return array_search($constant, $constants);
	}

	public function toDisplayString()
	{
		return TextCaseConverter::convert($this->toString(),
			TextCaseConverter::CAMEL_CASE,
			TextCaseConverter::BLANK_CASE);
	}

	public static function getAllConstants()
	{
		$cls = new ReflectionClass(get_called_class());
		$constants = $cls->getConstants();
		return array_values($constants);
	}
}
