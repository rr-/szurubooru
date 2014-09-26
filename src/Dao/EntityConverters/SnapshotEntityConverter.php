<?php
namespace Szurubooru\Dao\EntityConverters;

class SnapshotEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'time' => $entity->getTime(),
			'type' => $entity->getType(),
			'primaryKey' => $entity->getPrimaryKey(),
			'userId' => $entity->getUserId(),
			'operation' => $entity->getOperation(),
			'data' => json_encode($entity->getData()),
			'dataDifference' => json_encode($entity->getDataDifference()),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Snapshot(intval($array['id']));
		$entity->setTime($array['time']);
		$entity->setType(intval($array['type']));
		$entity->setPrimaryKey($array['primaryKey']);
		$entity->setUserId($array['userId']);
		$entity->setOperation($array['operation']);
		$entity->setData(json_decode($array['data'], true));
		$entity->setDataDifference(json_decode($array['dataDifference'], true));
		return $entity;
	}
}

