<?php
namespace Szurubooru\Dao\EntityConverters;

class PostEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
		];
	}

	public function toEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Post($array['id']);
		$entity->setName($array['name']);
		return $entity;
	}
}
