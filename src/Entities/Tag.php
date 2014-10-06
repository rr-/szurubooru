<?php
namespace Szurubooru\Entities;

final class Tag extends Entity
{
	private $name;

	const META_USAGES = 'usages';

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
		return $this->getMeta(self::META_USAGES);
	}
}
