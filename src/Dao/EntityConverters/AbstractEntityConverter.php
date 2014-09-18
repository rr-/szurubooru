<?php
namespace Szurubooru\Dao\EntityConverters;

abstract class AbstractEntityConverter implements IEntityConverter
{
	private $entityDecorator = null;

	public function setEntityDecorator($entityDecorator)
	{
		$this->entityDecorator = $entityDecorator;
	}

	public function toEntity(array $array)
	{
		$entity = $this->toBasicEntity($array);
		if ($this->entityDecorator !== null)
			call_user_func($this->entityDecorator, $entity);
		return $entity;
	}

	protected abstract function toBasicEntity(array $array);
}
