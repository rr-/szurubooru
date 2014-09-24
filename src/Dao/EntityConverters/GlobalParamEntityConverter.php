<?php
namespace Szurubooru\Dao\EntityConverters;

class GlobalParamEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'key' => $entity->getKey(),
			'value' => $entity->getValue(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\GlobalParam($array['id']);
		$entity->setKey($array['key']);
		$entity->setValue($array['value']);
		return $entity;
	}
}
