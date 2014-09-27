<?php
namespace Szurubooru\SearchServices\Requirements;

class RequirementCompositeValue implements IRequirementValue
{
	private $values = [];
	public function getValues()
	{
		return $this->values;
	}

	public function setValues(array $values)
	{
		$this->values = $values;
	}
}
