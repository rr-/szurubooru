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
		return $entity;
	}
}
