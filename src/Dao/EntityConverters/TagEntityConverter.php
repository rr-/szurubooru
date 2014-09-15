<?php
namespace Szurubooru\Dao\EntityConverters;

class TagEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'name' => $entity->getName(),
		];
	}

	public function toEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Tag($array['name']);
		$entity->setName($array['name']);
		return $entity;
	}
}
