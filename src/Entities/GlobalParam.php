<?php
namespace Szurubooru\Entities;

class GlobalParam extends Entity
{
	const KEY_FEATURED_POST = 'featuredPost';

	private $key;
	private $value;

	public function getKey()
	{
		return $this->key;
	}

	public function setKey($key)
	{
		$this->key = $key;
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
