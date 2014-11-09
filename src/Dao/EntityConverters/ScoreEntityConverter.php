<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Score;

class ScoreEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toBasicArray(Entity $entity)
	{
		return
		[
			'userId' => $entity->getUserId(),
			'postId' => $entity->getPostId(),
			'commentId' => $entity->getCommentId(),
			'time' => $this->entityTimeToDbTime($entity->getTime()),
			'score' => $entity->getScore(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new Score($array['id']);
		$entity->setUserId($array['userId']);
		$entity->setPostId($array['postId']);
		$entity->setCommentId($array['commentId']);
		$entity->setTime($this->dbTimeToEntityTime($array['time']));
		$entity->setScore(intval($array['score']));
		return $entity;
	}
}
