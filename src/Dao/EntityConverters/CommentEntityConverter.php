<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Comment;
use Szurubooru\Entities\Entity;

class CommentEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toBasicArray(Entity $entity)
	{
		return
		[
			'userId' => $entity->getUserId(),
			'postId' => $entity->getPostId(),
			'text' => $entity->getText(),
			'creationTime' => $this->entityTimeToDbTime($entity->getCreationTime()),
			'lastEditTime' => $this->entityTimeToDbTime($entity->getLastEditTime()),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new Comment($array['id']);
		$entity->setUserId($array['userId']);
		$entity->setPostId($array['postId']);
		$entity->setText($array['text']);
		$entity->setCreationTime($this->dbTimeToEntityTime($array['creationTime']));
		$entity->setLastEditTime($this->dbTimeToEntityTime($array['lastEditTime']));
		$entity->setMeta(Comment::META_SCORE, intval($array['score']));
		return $entity;
	}
}
