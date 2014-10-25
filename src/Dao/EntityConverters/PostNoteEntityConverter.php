<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\PostNote;

class PostNoteEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'postId' => $entity->getPostId(),
			'x' => $entity->getLeft(),
			'y' => $entity->getTop(),
			'width' => $entity->getWidth(),
			'height' => $entity->getHeight(),
			'text' => $entity->getText(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new PostNote($array['id']);
		$entity->setPostId($array['postId']);
		$entity->setLeft(intval($array['x']));
		$entity->setTop(intval($array['y']));
		$entity->setWidth(intval($array['width']));
		$entity->setHeight(intval($array['height']));
		$entity->setText($array['text']);
		return $entity;
	}
}
