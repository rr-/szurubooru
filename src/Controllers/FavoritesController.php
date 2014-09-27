<?php
namespace Szurubooru\Controllers;

class FavoritesController extends AbstractController
{
	private $privilegeService;
	private $authService;
	private $postService;
	private $favoritesService;
	private $userViewProxy;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\PostService $postService,
		\Szurubooru\Services\FavoritesService $favoritesService,
		\Szurubooru\Controllers\ViewProxies\UserViewProxy $userViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->authService = $authService;
		$this->postService = $postService;
		$this->favoritesService = $favoritesService;
		$this->userViewProxy = $userViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
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
