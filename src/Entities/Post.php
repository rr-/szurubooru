<?php
namespace Szurubooru\Entities;

final class Post extends Entity
{
	const POST_SAFETY_SAFE = 1;
	const POST_SAFETY_SKETCHY = 2;
	const POST_SAFETY_UNSAFE = 3;

	const POST_TYPE_IMAGE = 1;
	const POST_TYPE_FLASH = 2;
	const POST_TYPE_VIDEO = 3;
	const POST_TYPE_YOUTUBE = 4;

	const LAZY_LOADER_USER = 'user';
	const LAZY_LOADER_TAGS = 'tags';
	const LAZY_LOADER_CONTENT = 'content';
	const LAZY_LOADER_THUMBNAIL_SOURCE_CONTENT = 'thumbnailSourceContent';
	const LAZY_LOADER_RELATED_POSTS = 'relatedPosts';

	const META_TAG_COUNT = 'tagCount';

	protected $name;
	protected $userId;
	protected $uploadTime;
	protected $lastEditTime;
	protected $safety;
	protected $contentType;
	protected $contentChecksum;
	protected $contentMimeType;
	protected $source;
	protected $imageWidth;
	protected $imageHeight;
	protected $originalFileSize;
	protected $originalFileName;
	protected $featureCount = 0;
	protected $lastFeatureTime;

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

	public function setUser(\Szurubooru\Entities\User $user = null)
	{
		$this->lazySave(self::LAZY_LOADER_USER, $user);
		if ($user)
		{
			$this->userId = $user->getId();
		}
		else
		{
			$this->userId = null;
		}
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
}
