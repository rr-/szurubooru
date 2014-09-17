<?php
namespace Szurubooru\Controllers;

final class PostContentController extends AbstractController
{
	private $postService;
	private $fileService;
	private $httpHelper;
	private $thumbnailService;

	public function __construct(
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Helpers\HttpHelper $httpHelper,
		\Szurubooru\Services\ThumbnailService $thumbnailService)
	{
		$this->postService = $postService;
		$this->fileService = $fileService;
		$this->httpHelper = $httpHelper;
		$this->thumbnailService = $thumbnailService;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/posts/:postName/content', [$this, 'getPostContent']);
		$router->get('/api/posts/:postName/thumbnail/:size', [$this, 'getPostThumbnail']);
	}

	public function getPostContent($postName)
	{
		$post = $this->postService->getByName($postName);
		$source = $this->postService->getPostContentPath($post);
		$this->fileService->serve($source);
	}

	public function getPostThumbnail($postName, $size)
	{
		$post = $this->postService->getByName($postName);
		$source = $this->postService->getPostThumbnailSourcePath($post);
		if (!$this->fileService->exists($source))
			$source = $this->postService->getPostContentPath($post);

		$sizedSource = $this->thumbnailService->getOrGenerate($source, $size, $size);
		$this->fileService->serve($sizedSource);
	}
}
