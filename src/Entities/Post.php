<?php
namespace Szurubooru\Entities;

final class Post extends Entity
{
	protected $name;

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}
}
