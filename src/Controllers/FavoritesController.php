<?php
namespace Szurubooru\Controllers;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\Router;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\FavoritesService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\PrivilegeService;

final class FavoritesController extends AbstractController
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

	public function registerRoutes(Router $router)
	{
		$router->get('/api/posts/:postNameOrId/favorites', [$this, 'getFavoriteUsers']);
		$router->post('/api/posts/:postNameOrId/favorites', [$this, 'addFavorite']);
		$router->delete('/api/posts/:postNameOrId/favorites', [$this, 'deleteFavorite']);
	}

	public function getFavoriteUsers($postNameOrId)
	{
		$post = $this->postService->getByNameOrId($postNameOrId);
		$users = $this->favoritesService->getFavoriteUsers($post);
		return ['data' => $this->userViewProxy->fromArray($users)];
	}

	public function addFavorite($postNameOrId)
	{
		$this->privilegeService->assertLoggedIn();
		$user = $this->authService->getLoggedInUser();
		$post = $this->postService->getByNameOrId($postNameOrId);
		$this->favoritesService->addFavorite($user, $post);
		return $this->getFavoriteUsers($postNameOrId);
	}

	public function deleteFavorite($postNameOrId)
	{
		$this->privilegeService->assertLoggedIn();
		$user = $this->authService->getLoggedInUser();
		$post = $this->postService->getByNameOrId($postNameOrId);
		$this->favoritesService->deleteFavorite($user, $post);
		return $this->getFavoriteUsers($postNameOrId);
	}
}
