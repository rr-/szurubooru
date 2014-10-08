<?php
namespace Szurubooru\Dao\EntityConverters;

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

	protected abstract function toBasicEntity(array $array);

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
