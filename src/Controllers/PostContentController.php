<?php
namespace Szurubooru\Controllers;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Router;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\ThumbnailService;

final class PostContentController extends AbstractController
{
	private $fileDao;
	private $postService;
	private $networkingService;
	private $thumbnailService;

	public function __construct(
		PublicFileDao $fileDao,
		PostService $postService,
		NetworkingService $networkingService,
		ThumbnailService $thumbnailService)
	{
		$this->fileDao = $fileDao;
		$this->postService = $postService;
		$this->networkingService = $networkingService;
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
		$this->networkingService->serve($this->fileDao->getFullPath($post->getContentPath()));
	}

	public function getPostThumbnail($postName, $size)
	{
		$post = $this->postService->getByName($postName);

		$sourceName = $post->getThumbnailSourceContentPath();
		if (!$this->fileDao->exists($sourceName))
			$sourceName = $post->getContentPath();

		$this->thumbnailService->generateIfNeeded($sourceName, $size, $size);
		$thumbnailName = $this->thumbnailService->getThumbnailName($sourceName, $size, $size);
		$this->networkingService->serve($this->fileDao->getFullPath($thumbnailName));
	}
}
