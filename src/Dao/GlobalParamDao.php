<?php
namespace Szurubooru\Dao;

class GlobalParamDao extends AbstractDao implements ICrudDao
{
	public function __construct(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'globals',
			new \Szurubooru\Dao\EntityConverters\GlobalParamEntityConverter());
	}

	public function save(&$entity)
	{
		if (!$entity->getId())
		{
			$otherEntityWithThisKey = $this->findByKey($entity->getKey());
			if ($otherEntityWithThisKey)
				$entity->setId($otherEntityWithThisKey->getId());
		}
		parent::save($entity);
	}

	public function findByKey($key)
	{
		return $this->findOneBy('dataKey', $key);
	}

	public function deleteByKey($key)
	{
		return $this->deleteBy('dataKey', $key);
	}
}
