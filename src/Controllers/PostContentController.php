<?php
namespace Szurubooru\Controllers;
use Szurubooru\Config;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Entities\Post;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Router;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PostThumbnailService;

final class PostContentController extends AbstractController
{
	private $config;
	private $fileDao;
	private $postService;
	private $networkingService;
	private $postThumbnailService;

	public function __construct(
		Config $config,
		PublicFileDao $fileDao,
		PostService $postService,
		NetworkingService $networkingService,
		PostThumbnailService $postThumbnailService)
	{
		$this->config = $config;
		$this->fileDao = $fileDao;
		$this->postService = $postService;
		$this->networkingService = $networkingService;
		$this->postThumbnailService = $postThumbnailService;
	}

	public function registerRoutes(Router $router)
	{
		$router->get('/api/posts/:postName/content', [$this, 'getPostContent']);
		$router->get('/api/posts/:postName/thumbnail/:size', [$this, 'getPostThumbnail']);
	}

	public function getPostContent($postName)
	{
		$post = $this->postService->getByName($postName);

		$customFileName = sprintf('%s_%s.%s',
			$this->config->basic->serviceName,
			$post->getName(),
			strtolower(MimeHelper::getExtension($post->getContentMimeType())));

		if ($post->getContentType() === Post::POST_TYPE_YOUTUBE)
		{
			$this->networkingService->nonCachedRedirect($post->getOriginalFileName());
			return;
		}

		$this->networkingService->serveFile($this->fileDao->getFullPath($post->getContentPath()), $customFileName);
	}

	public function getPostThumbnail($postName, $size)
	{
		$post = $this->postService->getByName($postName);
		$thumbnailName = $this->postThumbnailService->generateIfNeeded($post, $size, $size);
		$this->networkingService->serveFile($this->fileDao->getFullPath($thumbnailName));
	}
}
