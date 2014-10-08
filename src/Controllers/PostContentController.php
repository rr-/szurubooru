<?php
namespace Szurubooru\Controllers;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\Router;
use Szurubooru\Services\FileService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\ThumbnailService;

final class PostContentController extends AbstractController
{
	private $postService;
	private $fileService;
	private $httpHelper;
	private $thumbnailService;

	public function __construct(
		PostService $postService,
		FileService $fileService,
		HttpHelper $httpHelper,
		ThumbnailService $thumbnailService)
	{
		$this->postService = $postService;
		$this->fileService = $fileService;
		$this->httpHelper = $httpHelper;
		$this->thumbnailService = $thumbnailService;
	}

	public function registerRoutes(Router $router)
	{
		$router->get('/api/posts/:postName/content', [$this, 'getPostContent']);
		$router->get('/api/posts/:postName/thumbnail/:size', [$this, 'getPostThumbnail']);
	}

	public function getPostContent($postName)
	{
		$post = $this->postService->getByName($postName);
		$this->fileService->serve($post->getContentPath());
	}

	public function getPostThumbnail($postName, $size)
	{
		$post = $this->postService->getByName($postName);

		$sourceName = $post->getThumbnailSourceContentPath();
		if (!$this->fileService->exists($sourceName))
			$sourceName = $post->getContentPath();

		$this->thumbnailService->generateIfNeeded($sourceName, $size, $size);
		$thumbnailName = $this->thumbnailService->getThumbnailName($sourceName, $size, $size);
		$this->fileService->serve($thumbnailName);
	}
}
