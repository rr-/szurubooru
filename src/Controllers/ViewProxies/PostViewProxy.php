<?php
namespace Szurubooru\Controllers\ViewProxies;

class PostViewProxy extends AbstractViewProxy
{
	private $tagViewProxy;
	private $userViewProxy;

	public function __construct(
		TagViewProxy $tagViewProxy,
		UserViewProxy $userViewProxy)
	{
		$this->tagViewProxy = $tagViewProxy;
		$this->userViewProxy = $userViewProxy;
	}

	public function fromEntity($post)
	{
		$result = new \StdClass;
		if ($post)
		{
			$result->id = $post->getId();
			$result->name = $post->getName();
			$result->uploadTime = $post->getUploadTime();
			$result->lastEditTime = $post->getLastEditTime();
			$result->safety = \Szurubooru\Helpers\EnumHelper::postSafetyToString($post->getSafety());
			$result->contentType = \Szurubooru\Helpers\EnumHelper::postTypeToString($post->getContentType());
			$result->contentChecksum = $post->getContentChecksum();
			$result->contentMimeType = $post->getContentMimeType();
			$result->source = $post->getSource();
			$result->imageWidth = $post->getImageWidth();
			$result->imageHeight = $post->getImageHeight();
			$result->tags = $this->tagViewProxy->fromArray($post->getTags());
			$result->originalFileSize = $post->getOriginalFileSize();
			$result->user = $this->userViewProxy->fromEntity($post->getUser());
		}
		return $result;
	}
}
