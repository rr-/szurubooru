<?php
namespace Szurubooru\Dao\EntityConverters;

class TagEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'name' => $entity->getName(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Tag($array['name']);
		$entity->setName($array['name']);
		return $entity;
	}
}
