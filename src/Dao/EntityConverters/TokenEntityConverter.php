<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Token;

class TokenEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
			'purpose' => $entity->getPurpose(),
			'additionalData' => $entity->getAdditionalData(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new Token(intval($array['id']));
		$entity->setName($array['name']);
		$entity->setPurpose($array['purpose']);
		$entity->setAdditionalData($array['additionalData']);
		return $entity;
	}
}
