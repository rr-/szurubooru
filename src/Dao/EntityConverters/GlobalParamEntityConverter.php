<?php
namespace Szurubooru\Dao\EntityConverters;

class GlobalParamEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'dataKey' => $entity->getKey(),
			'dataValue' => $entity->getValue(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\GlobalParam($array['id']);
		$entity->setKey($array['dataKey']);
		$entity->setValue($array['dataValue']);
		return $entity;
	}
}
