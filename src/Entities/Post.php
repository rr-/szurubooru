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

	public function setUser(\Szurubooru\Entities\User $user = null)
	{
		if ($user)
		{
			$this->userId = $user->getId();
		}
		else
		{
			$this->userId = null;
		}
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

	public function getTags()
	{
		return $this->lazyLoad('tags', []);
	}

	public function setTags(array $tags)
	{
		$this->lazySave('tags', $tags);
	}
}
