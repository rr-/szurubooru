<?php
namespace Szurubooru\Routes\Posts;
use Szurubooru\Controllers\ViewProxies\PostViewProxy;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\Entities\Post;
use Szurubooru\Services\PostFeatureService;

class GetFeaturedPost extends AbstractPostRoute
{
	private $postFeatureService;
	private $postViewProxy;

	public function __construct(
		PostFeatureService $postFeatureService,
		UserViewProxy $userViewProxy,
		PostViewProxy $postViewProxy)
	{
		$this->postFeatureService = $postFeatureService;
		$this->userViewProxy = $userViewProxy;
		$this->postViewProxy = $postViewProxy;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/posts/featured';
	}

	public function work($args)
	{
		$post = $this->postFeatureService->getFeaturedPost();
		$user = $this->postFeatureService->getFeaturedPostUser();
		return [
			'user' => $this->userViewProxy->fromEntity($user),
			'post' => $this->postViewProxy->fromEntity($post, $this->getFullFetchConfig()),
		];
	}
}
