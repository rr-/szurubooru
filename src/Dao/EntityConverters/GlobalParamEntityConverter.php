<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\GlobalParam;

class GlobalParamEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toBasicArray(Entity $entity)
	{
		return
		[
			'dataKey' => $entity->getKey(),
			'dataValue' => $entity->getValue(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new GlobalParam(intval($array['id']));
		$entity->setKey($array['dataKey']);
		$entity->setValue($array['dataValue']);
		return $entity;
	}
}
