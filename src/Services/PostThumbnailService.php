<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Entities\Post;
use Szurubooru\Services\ThumbnailService;

class PostThumbnailService
{
	private $thumbnailService;
	private $fileDao;

	public function __construct(
		PublicFileDao $fileDao,
		ThumbnailService $thumbnailService)
	{
		$this->fileDao = $fileDao;
		$this->thumbnailService = $thumbnailService;
	}

	public function generateIfNeeded(Post $post, $width, $height)
	{
		$sourceName = $post->getThumbnailSourceContentPath();
		if (!$this->fileDao->exists($sourceName))
			$sourceName = $post->getContentPath();

		return $this->thumbnailService->generateIfNeeded($sourceName, $width, $height);
	}
}
