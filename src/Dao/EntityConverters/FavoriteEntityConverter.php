<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Favorite;

class FavoriteEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toBasicArray(Entity $entity)
	{
		return
		[
			'userId' => $entity->getUserId(),
			'postId' => $entity->getPostId(),
			'time' => $this->entityTimeToDbTime($entity->getTime()),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new Favorite(intval($array['id']));
		$entity->setUserId($array['userId']);
		$entity->setPostId($array['postId']);
		$entity->setTime($this->dbTimeToEntityTime($array['time']));
		return $entity;
	}
}
