<?php
namespace Szurubooru\SearchServices\Requirements;

class RequirementRangedValue implements IRequirementValue
{
	private $minValue = null;
	private $maxValue = null;

	public function getMinValue()
	{
		return $this->minValue;
	}

	public function setMinValue($minValue)
	{
		$this->minValue = $minValue;
	}

	public function getMaxValue()
	{
		return $this->maxValue;
	}

	public function setMaxValue($maxValue)
	{
		$this->maxValue = $maxValue;
	}

	public function getValues()
	{
		return range($this->minValue, $this->maxValue);
	}
}
