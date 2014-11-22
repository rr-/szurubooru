<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Config;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Entities\Post;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\PostService;

class GetPostContent extends AbstractPostRoute
{
	private $config;
	private $fileDao;
	private $postService;
	private $networkingService;

	public function __construct(
		Config $config,
		PublicFileDao $fileDao,
		PostService $postService,
		NetworkingService $networkingService)
	{
		$this->config = $config;
		$this->fileDao = $fileDao;
		$this->postService = $postService;
		$this->networkingService = $networkingService;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/posts/:postName/content';
	}

	public function work($args)
	{
		$post = $this->postService->getByName($args['postName']);

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
}
