<?php
namespace Szurubooru\Entities;
use Szurubooru\Entities\User;

final class Post extends Entity
{
	const POST_SAFETY_SAFE = 1;
	const POST_SAFETY_SKETCHY = 2;
	const POST_SAFETY_UNSAFE = 3;

	const POST_TYPE_IMAGE = 1;
	const POST_TYPE_FLASH = 2;
	const POST_TYPE_VIDEO = 3;
	const POST_TYPE_YOUTUBE = 4;
	const POST_TYPE_ANIMATED_IMAGE = 5;

	const FLAG_LOOP = 1;

	const LAZY_LOADER_USER = 'user';
	const LAZY_LOADER_TAGS = 'tags';
	const LAZY_LOADER_CONTENT = 'content';
	const LAZY_LOADER_THUMBNAIL_SOURCE_CONTENT = 'thumbnailSourceContent';
	const LAZY_LOADER_RELATED_POSTS = 'relatedPosts';

	const META_TAG_COUNT = 'tagCount';
	const META_FAV_COUNT = 'favCount';
	const META_COMMENT_COUNT = 'commentCount';
	const META_SCORE = 'score';

	private $name;
	private $userId;
	private $uploadTime;
	private $lastEditTime;
	private $safety;
	private $contentType;
	private $contentChecksum;
	private $contentMimeType;
	private $source;
	private $imageWidth;
	private $imageHeight;
	private $originalFileSize;
	private $originalFileName;
	private $featureCount = 0;
	private $lastFeatureTime;
	private $flags = 0;

	public function getIdMarkdown()
	{
		return '@' . $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	public function getSafety()
	{
		return $this->safety;
	}

	public function setSafety($safety)
	{
		$this->safety = $safety;
	}

	public function getUploadTime()
	{
		return $this->uploadTime;
	}

	public function setUploadTime($uploadTime)
	{
		$this->uploadTime = $uploadTime;
	}

	public function getLastEditTime()
	{
		return $this->lastEditTime;
	}

	public function setLastEditTime($lastEditTime)
	{
		$this->lastEditTime = $lastEditTime;
	}

	public function getContentType()
	{
		return $this->contentType;
	}

	public function setContentType($contentType)
	{
		$this->contentType = $contentType;
	}

	public function getContentChecksum()
	{
		return $this->contentChecksum;
	}

	public function setContentChecksum($contentChecksum)
	{
		$this->contentChecksum = $contentChecksum;
	}

	public function getContentMimeType()
	{
		return $this->contentMimeType;
	}

	public function setContentMimeType($contentMimeType)
	{
		$this->contentMimeType = $contentMimeType;
	}

	public function getSource()
	{
		return $this->source;
	}

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function getImageWidth()
	{
		return $this->imageWidth;
	}

	public function setImageWidth($imageWidth)
	{
		$this->imageWidth = $imageWidth;
	}

	public function getImageHeight()
	{
		return $this->imageHeight;
	}

	public function setImageHeight($imageHeight)
	{
		$this->imageHeight = $imageHeight;
	}

	public function getOriginalFileSize()
	{
		return $this->originalFileSize;
	}

	public function setOriginalFileSize($originalFileSize)
	{
		$this->originalFileSize = $originalFileSize;
	}

	public function getOriginalFileName()
	{
		return $this->originalFileName;
	}

	public function setOriginalFileName($originalFileName)
	{
		$this->originalFileName = $originalFileName;
	}

	public function getFeatureCount()
	{
		return $this->featureCount;
	}

	public function setFeatureCount($featureCount)
	{
		$this->featureCount = $featureCount;
	}

	public function getLastFeatureTime()
	{
		return $this->lastFeatureTime;
	}

	public function setLastFeatureTime($lastFeatureTime)
	{
		$this->lastFeatureTime = $lastFeatureTime;
	}

	public function getFlags()
	{
		return $this->flags;
	}

	public function setFlags($flags)
	{
		$this->flags = $flags;
	}

	public function getTags()
	{
		return $this->lazyLoad(self::LAZY_LOADER_TAGS, []);
	}

	public function setTags(array $tags)
	{
		$this->lazySave(self::LAZY_LOADER_TAGS, $tags);
		$this->setMeta(self::META_TAG_COUNT, count($tags));
	}

	public function getRelatedPosts()
	{
		return $this->lazyLoad(self::LAZY_LOADER_RELATED_POSTS, []);
	}

	public function setRelatedPosts(array $relatedPosts)
	{
		$this->lazySave(self::LAZY_LOADER_RELATED_POSTS, $relatedPosts);
	}

	public function getUser()
	{
		return $this->lazyLoad(self::LAZY_LOADER_USER, null);
	}

	public function setUser(User $user = null)
	{
		$this->lazySave(self::LAZY_LOADER_USER, $user);
		$this->userId = $user ? $user->getId() : null;
	}

	public function getContent()
	{
		return $this->lazyLoad(self::LAZY_LOADER_CONTENT, null);
	}

	public function setContent($content)
	{
		$this->lazySave(self::LAZY_LOADER_CONTENT, $content);
	}

	public function getThumbnailSourceContent()
	{
		return $this->lazyLoad(self::LAZY_LOADER_THUMBNAIL_SOURCE_CONTENT, null);
	}

	public function setThumbnailSourceContent($content)
	{
		$this->lazySave(self::LAZY_LOADER_THUMBNAIL_SOURCE_CONTENT, $content);
	}

	public function getContentPath()
	{
		return 'posts' . DIRECTORY_SEPARATOR . $this->getName();
	}

	public function getThumbnailSourceContentPath()
	{
		return 'posts' . DIRECTORY_SEPARATOR . $this->getName() . '-custom-thumb';
	}

	public function getTagCount()
	{
		return $this->getMeta(self::META_TAG_COUNT, 0);
	}

	public function getFavoriteCount()
	{
		return $this->getMeta(self::META_FAV_COUNT, 0);
	}

	public function getCommentCount()
	{
		return $this->getMeta(self::META_COMMENT_COUNT, 0);
	}

	public function getScore()
	{
		return $this->getMeta(self::META_SCORE, 0);
	}
}
