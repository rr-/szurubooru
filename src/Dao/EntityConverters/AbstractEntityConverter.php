<?php
namespace Szurubooru\Dao\EntityConverters;

use Szurubooru\Entities\Entity;

abstract class AbstractEntityConverter implements IEntityConverter
{
	private $entityDecorator = null;

	public function setEntityDecorator(callable $entityDecorator)
	{
		$this->entityDecorator = $entityDecorator;
	}

	public function toEntity(array $array)
	{
		$entity = $this->toBasicEntity($array);
		$func = $this->entityDecorator;
		if ($func !== null)
			$func($entity);
		return $entity;
	}

	public function toArray(Entity $entity)
	{
		$array = $this->toBasicArray($entity);
		if ($entity->getId() !== null)
			$array['id'] = $entity->getId();
		return $array;
	}

	protected abstract function toBasicEntity(array $array);

	protected abstract function toBasicArray(Entity $entity);

	protected function dbTimeToEntityTime($time)
	{
		if ($time === null)
			return null;
		return date('c', strtotime($time));
	}

	protected function entityTimeToDbTime($time)
	{
		return $time;
	}
}
