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
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Tag($array['id']);
		$entity->setName($array['name']);
		$entity->setMeta(\Szurubooru\Entities\Tag::META_USAGES, intval($array['usages']));
		return $entity;
	}
}
