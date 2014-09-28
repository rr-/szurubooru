<?php
namespace Szurubooru\Dao\EntityConverters;

class PostScoreEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'userId' => $entity->getUserId(),
			'postId' => $entity->getPostId(),
			'time' => $entity->getTime(),
			'score' => $entity->getScore(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\PostScore($array['id']);
		$entity->setUserId($array['userId']);
		$entity->setPostId($array['postId']);
		$entity->setTime($array['time']);
		$entity->setScore(intval($array['score']));
		return $entity;
	}
}
