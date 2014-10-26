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
		$entity->setLeft(floatval($array['x']));
		$entity->setTop(floatval($array['y']));
		$entity->setWidth(floatval($array['width']));
		$entity->setHeight(floatval($array['height']));
		$entity->setText($array['text']);
		return $entity;
	}
}
