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
		$entity->setLeft($array['x']);
		$entity->setTop($array['y']);
		$entity->setWidth($array['width']);
		$entity->setHeight($array['height']);
		$entity->setText($array['text']);
		return $entity;
	}
}
