<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Tag;

class TagEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
			'creationTime' => $this->entityTimeToDbTime($entity->getCreationTime()),
			'banned' => $entity->isBanned(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new Tag($array['id']);
		$entity->setName($array['name']);
		$entity->setCreationTime($this->dbTimeToEntityTime($array['creationTime']));
		$entity->setMeta(Tag::META_USAGES, intval($array['usages']));
		$entity->setBanned($array['banned']);
		return $entity;
	}
}
