<?php
namespace Szurubooru\Dao;

abstract class AbstractDao implements ICrudDao
{
	protected $pdo;
	protected $fpdo;
	protected $tableName;
	protected $entityConverter;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		$tableName,
		\Szurubooru\Dao\EntityConverters\IEntityConverter $entityConverter)
	{
		$this->pdo = $databaseConnection->getPDO();
		$this->fpdo = new \FluentPDO($this->pdo);
		$this->tableName = $tableName;
		$this->entityConverter = $entityConverter;
		$this->entityConverter->setEntityDecorator(function($entity)
			{
				$this->afterLoad($entity);
			});
	}

	public function getTableName()
	{
		return $this->tableName;
	}

	public function getEntityConverter()
	{
		return $this->entityConverter;
	}

	public function save(&$entity)
	{
		if ($entity->getId())
		{
			$entity = $this->update($entity);
		}
		else
		{
			$entity = $this->create($entity);
		}
		$this->afterSave($entity);
		return $entity;
	}

	public function findAll()
	{
		$entities = [];
		$query = $this->fpdo->from($this->tableName);
		foreach ($query as $arrayEntity)
		{
			$entity = $this->entityConverter->toEntity($arrayEntity);
			$entities[$entity->getId()] = $entity;
		}
		return $entities;
	}

	public function findById($entityId)
	{
		return $this->findOneBy('id', $entityId);
	}

	public function deleteAll()
	{
		foreach ($this->findAll() as $entity)
		{
			$this->beforeDelete($entity);
		}
		$this->fpdo->deleteFrom($this->tableName)->execute();
	}

	public function deleteById($entityId)
	{
		return $this->deleteBy('id', $entityId);
	}

	protected function update(\Szurubooru\Entities\Entity $entity)
	{
		$arrayEntity = $this->entityConverter->toArray($entity);
		$this->fpdo->update($this->tableName)->set($arrayEntity)->where('id', $entity->getId())->execute();
		return $entity;
	}

	protected function create(\Szurubooru\Entities\Entity $entity)
	{
		$arrayEntity = $this->entityConverter->toArray($entity);
		$this->fpdo->insertInto($this->tableName)->values($arrayEntity)->execute();
		$entity->setId(intval($this->pdo->lastInsertId()));
		return $entity;
	}

	protected function hasAnyRecords()
	{
		return count(iterator_to_array($this->fpdo->from($this->tableName)->limit(1))) > 0;
	}

	protected function findBy($columnName, $value)
	{
		$entities = [];
		$query = $this->fpdo->from($this->tableName)->where($columnName, $value);
		foreach ($query as $arrayEntity)
		{
			$entity = $this->entityConverter->toEntity($arrayEntity);
			$entities[$entity->getId()] = $entity;
		}
		return $entities;
	}

	protected function findOneBy($columnName, $value)
	{
		$arrayEntities = $this->findBy($columnName, $value);
		if (!$arrayEntities)
			return null;
		return array_shift($arrayEntities);
	}

	protected function deleteBy($columnName, $value)
	{
		foreach ($this->findBy($columnName, $value) as $entity)
		{
			$this->beforeDelete($entity);
		}
		$this->fpdo->deleteFrom($this->tableName)->where($columnName, $value)->execute();
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $entity)
	{
	}

	protected function afterSave(\Szurubooru\Entities\Entity $entity)
	{
	}

	protected function beforeDelete(\Szurubooru\Entities\Entity $entity)
	{
	}
}
