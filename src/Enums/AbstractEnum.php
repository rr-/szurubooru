<?php
abstract class AbstractEnum implements IEnum
{
	public abstract function toString();

	public function toDisplayString()
	{
		return TextCaseConverter::convert($this->toString(),
			TextCaseConverter::SPINAL_CASE,
			TextCaseConverter::BLANK_CASE);
	}

	public static function getAllConstants()
	{
		$cls = new ReflectionClass(get_called_class());
		$constants = $cls->getConstants();
		return array_values($constants);
	}
}
