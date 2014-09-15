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
		$arrayEntity = $this->entityConverter->toArray($entity);
		if ($entity->getId())
		{
			$this->fpdo->update($this->tableName)->set($arrayEntity)->where('id', $entity->getId())->execute();
		}
		else
		{
			$this->fpdo->insertInto($this->tableName)->values($arrayEntity)->execute();
			$arrayEntity['id'] = $this->pdo->lastInsertId();
		}
		$entity = $this->entityConverter->toEntity($arrayEntity);
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
		$this->fpdo->deleteFrom($this->tableName)->execute();
	}

	public function deleteById($entityId)
	{
		return $this->deleteBy('id', $entityId);
	}

	protected function hasAnyRecords()
	{
		return count(iterator_to_array($this->fpdo->from($this->tableName)->limit(1))) > 0;
	}

	protected function findOneBy($columnName, $value)
	{
		$arrayEntity = iterator_to_array($this->fpdo->from($this->tableName)->where($columnName, $value));
		return $arrayEntity ? $this->entityConverter->toEntity($arrayEntity[0]) : null;
	}

	protected function deleteBy($columnName, $value)
	{
		$this->fpdo->deleteFrom($this->tableName)->where($columnName, $value)->execute();
	}
}
