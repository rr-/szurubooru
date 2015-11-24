<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Post;

class PostEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
    public function toBasicArray(Entity $entity)
    {
        return
        [
            'name' => $entity->getName(),
            'userId' => $entity->getUserId(),
            'creationTime' => $this->entityTimeToDbTime($entity->getCreationTime()),
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
            'flags' => $entity->getFlags(),
        ];
    }

    public function toBasicEntity(array $array)
    {
        $entity = new Post(intval($array['id']));
        $entity->setName($array['name']);
        $entity->setUserId($array['userId']);
        $entity->setCreationTime($this->dbTimeToEntityTime($array['creationTime']));
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
        $entity->setFlags(intval($array['flags']));
        $entity->setMeta(Post::META_TAG_COUNT, intval($array['tagCount']));
        $entity->setMeta(Post::META_FAV_COUNT, intval($array['favCount']));
        $entity->setMeta(Post::META_COMMENT_COUNT, intval($array['commentCount']));
        $entity->setMeta(Post::META_SCORE, intval($array['score']));
        return $entity;
    }
}
