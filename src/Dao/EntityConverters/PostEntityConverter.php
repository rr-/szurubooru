<?php
namespace Szurubooru\Dao\EntityConverters;

class PostEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
			'userId' => $entity->getUserId(),
			'uploadTime' => $entity->getUploadTime(),
			'lastEditTime' => $entity->getLastEditTime(),
			'safety' => $entity->getSafety(),
			'contentType' => $entity->getContentType(),
			'contentChecksum' => $entity->getContentChecksum(),
			'source' => $entity->getSource(),
			'imageWidth' => $entity->getImageWidth(),
			'imageHeight' => $entity->getImageHeight(),
			'originalFileSize' => $entity->getOriginalFileSize(),
			'originalFileName' => $entity->getOriginalFileName(),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Post(intval($array['id']));
		$entity->setName($array['name']);
		$entity->setUserId($array['userId']);
		$entity->setUploadTime($array['uploadTime']);
		$entity->setLastEditTime($array['lastEditTime']);
		$entity->setSafety(intval($array['safety']));
		$entity->setContentType(intval($array['contentType']));
		$entity->setContentChecksum($array['contentChecksum']);
		$entity->setSource($array['source']);
		$entity->setImageWidth($array['imageWidth']);
		$entity->setImageHeight($array['imageHeight']);
		$entity->setOriginalFileSize($array['originalFileSize']);
		$entity->setOriginalFileName($array['originalFileName']);
		return $entity;
	}
}
