<?php
namespace Szurubooru\Dao\EntityConverters;

class TagEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
			'creationTime' => $this->entityTimeToDbTime($entity->getCreationTime()),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Tag($array['id']);
		$entity->setName($array['name']);
		$entity->setCreationTime($this->dbTimeToEntityTime($array['creationTime']));
		$entity->setMeta(\Szurubooru\Entities\Tag::META_USAGES, intval($array['usages']));
		return $entity;
	}
}
