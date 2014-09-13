<?php
namespace Szurubooru\Dao;

abstract class AbstractDao implements ICrudDao
{
	protected $db;
	protected $collection;
	protected $entityName;
	protected $entityConverter;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		$collectionName,
		$entityName)
	{
		$this->entityConverter = new EntityConverter($entityName);
		$this->db = $databaseConnection->getDatabase();
		$this->collection = $this->db->selectCollection($collectionName);
		$this->entityName = $entityName;
	}

	public function getCollection()
	{
		return $this->collection;
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
			$savedId = $arrayEntity['_id'];
			unset($arrayEntity['_id']);
			$this->collection->update(['_id' => new \MongoId($entity->getId())], $arrayEntity, ['w' => true]);
			$arrayEntity['_id'] = $savedId;
		}
		else
		{
			$this->collection->insert($arrayEntity, ['w' => true]);
		}
		$entity = $this->entityConverter->toEntity($arrayEntity);
		return $entity;
	}

	public function findAll()
	{
		$entities = [];
		foreach ($this->collection->find() as $key => $arrayEntity)
		{
			$entity = $this->entityConverter->toEntity($arrayEntity);
			$entities[$key] = $entity;
		}
		return $entities;
	}

	public function findById($entityId)
	{
		$arrayEntity = $this->collection->findOne(['_id' => new \MongoId($entityId)]);
		return $this->entityConverter->toEntity($arrayEntity);
	}

	public function deleteAll()
	{
		$this->collection->remove();
	}

	public function deleteById($entityId)
	{
		$this->collection->remove(['_id' => new \MongoId($entityId)]);
	}
}
