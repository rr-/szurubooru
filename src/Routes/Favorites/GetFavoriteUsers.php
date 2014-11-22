<?php
namespace Szurubooru\Routes\Favorites;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\FavoritesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

class GetFavoriteUsers extends AbstractRoute
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $favoritesService;
	private $userViewProxy;

	public function __construct(
		PrivilegeService $privilegeService,
		AuthService $authService,
		PostService $postService,
		FavoritesService $favoritesService,
		UserViewProxy $userViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->favoritesService = $favoritesService;
		$this->userViewProxy = $userViewProxy;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/posts/:postNameOrId/favorites';
	}

	public function work($args)
	{
		$post = $this->postService->getByNameOrId($args['postNameOrId']);
		$users = $this->favoritesService->getFavoriteUsers($post);
		return ['data' => $this->userViewProxy->fromArray($users)];
	}
}
