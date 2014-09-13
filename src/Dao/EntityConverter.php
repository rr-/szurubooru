<?php
namespace Szurubooru\Dao;

final class EntityConverter
{
	protected $entityName;

	public function __construct($entityName)
	{
		$this->entityName = $entityName;
	}

	public function toArray($entity)
	{
		$arrayEntity = [];
		$reflectionClass = new \ReflectionClass($this->entityName);
		$reflectionProperties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
		foreach ($reflectionProperties as $reflectionProperty)
		{
			$reflectionProperty->setAccessible(true);
			$arrayEntity[$reflectionProperty->getName()] = $reflectionProperty->getValue($entity);
		}
		if ($entity->getId())
		{
			$arrayEntity['_id'] = $arrayEntity['id'];
			unset($arrayEntity['id']);
		}
		return $arrayEntity;
	}

	public function toEntity($arrayEntity)
	{
		if ($arrayEntity === null)
			return null;

		$entity = new $this->entityName;
		$reflectionClass = new \ReflectionClass($this->entityName);

		$reflectionProperties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
		foreach ($reflectionProperties as $reflectionProperty)
		{
			if (isset($arrayEntity[$reflectionProperty->getName()]))
			{
				$reflectionProperty->setAccessible(true);
				$reflectionProperty->setValue($entity, $arrayEntity[$reflectionProperty->getName()]);
			}
		}

		$reflectionProperty = $reflectionClass->getProperty('id');
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue($entity, isset($arrayEntity['_id'])
			? (string) $arrayEntity['_id']
			: null);

		return $entity;
	}
}
