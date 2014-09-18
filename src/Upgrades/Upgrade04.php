<?php
namespace Szurubooru\Upgrades;

class Upgrade04 implements IUpgrade
{
	private $postService;
	private $fileService;

	public function __construct(
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Services\FileService $fileService)
	{
		$this->postService = $postService;
		$this->fileService = $fileService;
	}

	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$databaseConnection->getPDO()->exec('ALTER TABLE "posts" ADD COLUMN contentMimeType TEXT DEFAULT NULL');

		$postDao = new \Szurubooru\Dao\PostDao($databaseConnection);
		$posts = $postDao->findAll();
		foreach ($posts as $post)
		{
			if ($post->getContentType() !== \Szurubooru\Entities\Post::POST_TYPE_YOUTUBE)
			{
				$fullPath = $this->fileService->getFullPath($this->postService->getPostContentPath($post));
				$mime = \Szurubooru\Helpers\MimeHelper::getMimeTypeFromFile($fullPath);
				$post->setContentMimeType($mime);
				$postDao->save($post);
			}
		}
	}
}
