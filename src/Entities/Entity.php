<?php
namespace Szurubooru\Entities;

abstract class Entity
{
	protected $id = null;

	public function __construct($id = null)
	{
		$this->id = $id === null ? null : intval($id);
	}

	public function getId()
	{
		return $this->id;
	}
}
