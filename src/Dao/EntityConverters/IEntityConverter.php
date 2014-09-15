<?php
namespace Szurubooru\Dao\EntityConverters;

interface IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity);

	public function toEntity(array $array);
}
