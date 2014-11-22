<?php
namespace Szurubooru\Routes\Favorites;
use Szurubooru\Routes\AbstractRoute;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\FavoritesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\ViewProxies\UserViewProxy;

class RemoveFromFavorites extends AbstractRoute
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
		return ['DELETE'];
	}

	public function getUrl()
	{
		return '/api/posts/:postNameOrId/favorites';
	}

	public function work($args)
	{
		$this->privilegeService->assertLoggedIn();
		$user = $this->authService->getLoggedInUser();
		$post = $this->postService->getByNameOrId($args['postNameOrId']);
		$this->favoritesService->deleteFavorite($user, $post);

		$users = $this->favoritesService->getFavoriteUsers($post);
		return ['data' => $this->userViewProxy->fromArray($users)];
	}
}
