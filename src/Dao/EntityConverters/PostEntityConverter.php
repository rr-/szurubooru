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
			'uploadTime' => $this->entityTimeToDbTime($entity->getUploadTime()),
			'lastEditTime' => $this->entityTimeToDbTime($entity->getLastEditTime()),
			'safety' => $entity->getSafety(),
			'contentType' => $entity->getContentType(),
			'contentChecksum' => $entity->getContentChecksum(),
			'contentMimeType' => $entity->getContentMimeType(),
			'source' => $entity->getSource(),
			'imageWidth' => $entity->getImageWidth(),
			'imageHeight' => $entity->getImageHeight(),
			'originalFileSize' => $entity->getOriginalFileSize(),
			'originalFileName' => $entity->getOriginalFileName(),
			'featureCount' => $entity->getFeatureCount(),
			'lastFeatureTime' => $this->entityTimeToDbTime($entity->getLastFeatureTime()),
		];
	}

	public function toBasicEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\Post(intval($array['id']));
		$entity->setName($array['name']);
		$entity->setUserId($array['userId']);
		$entity->setUploadTime($this->dbTimeToEntityTime($array['uploadTime']));
		$entity->setLastEditTime($this->dbTimeToEntityTime($array['lastEditTime']));
		$entity->setSafety(intval($array['safety']));
		$entity->setContentType(intval($array['contentType']));
		$entity->setContentChecksum($array['contentChecksum']);
		$entity->setContentMimeType($array['contentMimeType']);
		$entity->setSource($array['source']);
		$entity->setImageWidth($array['imageWidth']);
		$entity->setImageHeight($array['imageHeight']);
		$entity->setOriginalFileSize($array['originalFileSize']);
		$entity->setOriginalFileName($array['originalFileName']);
		$entity->setFeatureCount(intval($array['featureCount']));
		$entity->setLastFeatureTime($this->dbTimeToEntityTime($array['lastFeatureTime']));
		$entity->setMeta(\Szurubooru\Entities\Post::META_TAG_COUNT, intval($array['tagCount']));
		$entity->setMeta(\Szurubooru\Entities\Post::META_FAV_COUNT, intval($array['favCount']));
		$entity->setMeta(\Szurubooru\Entities\Post::META_COMMENT_COUNT, intval($array['commentCount']));
		$entity->setMeta(\Szurubooru\Entities\Post::META_SCORE, intval($array['score']));
		return $entity;
	}
}
