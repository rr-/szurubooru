<?php
namespace Szurubooru\Dao;

final class EntityConverter
{
	private $entityName;
	private $reflectionClass;
	private $getterMethods = [];
	private $setterMethods = [];

	public function __construct($entityName)
	{
		$this->entityName = $entityName;

		$this->reflectionClass = new \ReflectionClass($this->entityName);
		$reflectionMethods = $this->reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($reflectionMethods as $reflectionMethod)
		{
			if (preg_match('/^(get|is)(\w+)$/', $reflectionMethod->getName(), $matches))
			{
				$columnName = lcfirst($matches[2]);
				$this->getterMethods[] = [$reflectionMethod, $columnName];
			}
			else if (preg_matcH('/^set(\w+)$/', $reflectionMethod->getName(), $matches))
			{
				$columnName = lcfirst($matches[1]);
				$this->setterMethods[] = [$reflectionMethod, $columnName];
			}
		}
	}

	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		$arrayEntity = [];

		foreach ($this->getterMethods as $kv)
		{
			list ($reflectionMethod, $columnName) = $kv;
			$value = $reflectionMethod->invoke($entity);
			$arrayEntity[$columnName] = $value;
		}
		return $arrayEntity;
	}

	public function toEntity($arrayEntity)
	{
		if ($arrayEntity === null)
			return null;

		$args = [$arrayEntity['id']];
		$entity = $this->reflectionClass->newInstanceArgs($args);

		foreach ($this->setterMethods as $kv)
		{
			list ($reflectionMethod, $columnName) = $kv;
			$value = $arrayEntity[$columnName];
			$reflectionMethod->invoke($entity, $value);
		}

		return $entity;
	}
}
