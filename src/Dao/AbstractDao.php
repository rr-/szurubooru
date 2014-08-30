<?php
namespace Szurubooru\Dao;

abstract class AbstractDao implements ICrudDao
{
	protected $db;
	protected $collection;
	protected $entityName;

	public function __construct(\Szurubooru\Config $config, $collectionName, $entityName)
	{
		$this->db = (new \Szurubooru\DatabaseConnection($config))->getDatabase();
		$this->collection = $this->db->selectCollection($collectionName);
		$this->entityName = $entityName;
	}

	public function save(&$entity)
	{
		$arrayEntity = $this->makeArray($entity);
		if ($entity->id)
		{
			unset ($arrayEntity['_id']);
			$this->collection->update(['_id' => new \MongoId($entity->id)], $arrayEntity, ['safe' => true]);
		}
		else
		{
			$this->collection->insert($arrayEntity, ['safe' => true]);
		}
		$entity = $this->makeEntity($arrayEntity);
		return $entity;
	}

	public function getAll()
	{
		$entities = [];
		foreach ($this->collection->find() as $key => $arrayEntity)
		{
			$entity = $this->makeEntity($arrayEntity);
			$entities[$key] = $entity;
		}
		return $entities;
	}

	public function getById($postId)
	{
		$arrayEntity = $this->collection->findOne(['_id' => new \MongoId($postId)]);
		return $this->makeEntity($arrayEntity);
	}

	public function deleteAll()
	{
		$this->collection->remove();
	}

	public function deleteById($postId)
	{
		$this->collection->remove(['_id' => new \MongoId($postId)]);
	}

	private function makeArray($entity)
	{
		$arrayEntity = (array) $entity;
		if (isset($entity->id))
		{
			$arrayEntity['_id'] = $arrayEntity['id'];
			unset($arrayEntity['id']);
		}
		return $arrayEntity;
	}

	private function makeEntity($arrayEntity)
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
