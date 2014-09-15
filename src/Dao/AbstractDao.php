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
		$this->tableName = $tableName;
		$this->entityConverter = $entityConverter;

		$this->pdo = $databaseConnection->getPDO();
		$this->fpdo = new \FluentPDO($this->pdo);
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
			$this->afterLoad($entity);
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

	protected function findOneBy($columnName, $value)
	{
		$arrayEntity = iterator_to_array($this->fpdo->from($this->tableName)->where($columnName, $value));
		if (!$arrayEntity)
			return null;

		$entity = $this->entityConverter->toEntity($arrayEntity[0]);
		$this->afterLoad($entity);
		return $entity;
	}

	protected function deleteBy($columnName, $value)
	{
		$this->fpdo->deleteFrom($this->tableName)->where($columnName, $value)->execute();
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $entity)
	{
	}

	protected function afterSave(\Szurubooru\Entities\Entity $entity)
	{
	}
}
