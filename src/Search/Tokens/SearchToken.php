<?php
namespace Szurubooru\Search\Tokens;

class SearchToken
{
	private $negated = false;
	private $value;

	public function isNegated()
	{
		return $this->negated;
	}

	public function setNegated($negated)
	{
		$this->negated = $negated;
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
