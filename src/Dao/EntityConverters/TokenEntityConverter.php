<?php
namespace Szurubooru\Dao\EntityConverters;

class TokenEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
			'purpose' => $entity->getPurpose(),
			'additionalData' => $entity->getAdditionalData(),
		];
	}

	public function toEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Token(intval($array['id']));
		$entity->setName($array['name']);
		$entity->setPurpose($array['purpose']);
		$entity->setAdditionalData($array['additionalData']);
		return $entity;
	}
}
