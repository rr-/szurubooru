<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;

interface IEntityConverter
{
    public function toArray(Entity $entity);

    public function toEntity(array $array);
}
