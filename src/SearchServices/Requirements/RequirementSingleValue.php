<?php
namespace Szurubooru\SearchServices\Requirements;

class RequirementSingleValue implements IRequirementValue
{
	private $value;

	public function __construct($value)
	{
		$this->setValue($value);
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}
}
