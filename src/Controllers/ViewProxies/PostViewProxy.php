<?php
namespace Szurubooru\Controllers\ViewProxies;

class PostViewProxy extends AbstractViewProxy
{
	const FETCH_USER = 'fetchUser';
	const FETCH_TAGS = 'fetchTags';
	const FETCH_RELATIONS = 'fetchRelations';

	private $tagViewProxy;
	private $userViewProxy;

	public function __construct(
		TagViewProxy $tagViewProxy,
		UserViewProxy $userViewProxy)
	{
		$this->tagViewProxy = $tagViewProxy;
		$this->userViewProxy = $userViewProxy;
	}

	public function fromEntity($post, $config = [])
	{
		$result = new \StdClass;
		if ($post)
		{
			$result->id = $post->getId();
			$result->idMarkdown = $post->getIdMarkdown();
			$result->name = $post->getName();
			$result->uploadTime = $post->getUploadTime();
			$result->lastEditTime = $post->getLastEditTime();
			$result->safety = \Szurubooru\Helpers\EnumHelper::postSafetyToString($post->getSafety());
			$result->contentType = \Szurubooru\Helpers\EnumHelper::postTypeToString($post->getContentType());
			$result->contentChecksum = $post->getContentChecksum();
			$result->contentMimeType = $post->getContentMimeType();
			$result->contentExtension = \Szurubooru\Helpers\MimeHelper::getExtension($post->getContentMimeType());
			$result->source = $post->getSource();
			$result->imageWidth = $post->getImageWidth();
			$result->imageHeight = $post->getImageHeight();
			$result->featureCount = $post->getFeatureCount();
			$result->lastFeatureTime = $post->getLastFeatureTime();
			$result->originalFileSize = $post->getOriginalFileSize();
			$result->favoriteCount = $post->getFavoriteCount();

			if (!empty($config[self::FETCH_TAGS]))
				$result->tags = $this->tagViewProxy->fromArray($post->getTags());

			if (!empty($config[self::FETCH_USER]))
				$result->user = $this->userViewProxy->fromEntity($post->getUser());

			if (!empty($config[self::FETCH_RELATIONS]))
				$result->relations = $this->fromArray($post->getRelatedPosts());
		}
		return $result;
	}
}
