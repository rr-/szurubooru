<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\GlobalParam;

class GlobalParamEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(Entity $entity)
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
		$entity = new GlobalParam($array['id']);
		$entity->setKey($array['dataKey']);
		$entity->setValue($array['dataValue']);
		return $entity;
	}
}
