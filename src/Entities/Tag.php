<?php
namespace Szurubooru\Entities;

final class Tag extends Entity
{
	protected $name;
	protected $usages;

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getUsages()
	{
		return $this->usages;
	}

	public function setUsages($usages)
	{
		$this->usages = $usages;
	}
}
