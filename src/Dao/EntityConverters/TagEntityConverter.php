<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Tag;

class TagEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
    public function toBasicArray(Entity $entity)
    {
        return
        [
            'name' => $entity->getName(),
            'creationTime' => $this->entityTimeToDbTime($entity->getCreationTime()),
            'lastEditTime' => $this->entityTimeToDbTime($entity->getLastEditTime()),
            'banned' => intval($entity->isBanned()),
            'category' => $entity->getCategory(),
        ];
    }

    public function toBasicEntity(array $array)
    {
        $entity = new Tag(intval($array['id']));
        $entity->setName($array['name']);
        $entity->setCreationTime($this->dbTimeToEntityTime($array['creationTime']));
        $entity->setLastEditTime($this->dbTimeToEntityTime($array['lastEditTime']));
        $entity->setMeta(Tag::META_USAGES, intval($array['usages']));
        $entity->setBanned($array['banned']);
        $entity->setCategory($array['category']);
        return $entity;
    }
}
