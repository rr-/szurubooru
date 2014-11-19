<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Snapshot;

class SnapshotEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toBasicArray(Entity $entity)
	{
		return
		[
			'time' => $this->entityTimeToDbTime($entity->getTime()),
			'type' => $entity->getType(),
			'primaryKey' => $entity->getPrimaryKey(),
			'userId' => $entity->getUserId(),
			'operation' => $entity->getOperation(),
			'data' => gzdeflate(json_encode($entity->getData())),
			'dataDifference' => gzdeflate(json_encode($entity->getDataDifference())),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new Snapshot(intval($array['id']));
		$entity->setTime($this->dbTimeToEntityTime($array['time']));
		$entity->setType(intval($array['type']));
		$entity->setPrimaryKey($array['primaryKey']);
		$entity->setUserId(intval($array['userId']));
		$entity->setOperation($array['operation']);
		$entity->setData(json_decode(gzinflate($array['data']), true));
		$entity->setDataDifference(json_decode(gzinflate($array['dataDifference']), true));
		return $entity;
	}
}
