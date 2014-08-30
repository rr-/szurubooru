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
		$arrayEntity = (array) $entity;
		if (isset($entity->id))
		{
			$arrayEntity['_id'] = $arrayEntity['id'];
			unset($arrayEntity['id']);
		}
		return $arrayEntity;
	}

	public function toEntity($arrayEntity)
	{
		$entity = \Szurubooru\Helpers\TypeHelper::arrayToClass($arrayEntity, $this->entityName);
		if (isset($entity->_id))
		{
			$entity->id = (string) $entity->_id;
			unset($entity->_id);
		}
		return $entity;
	}
}
