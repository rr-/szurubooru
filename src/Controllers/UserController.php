<?php
namespace Szurubooru\Controllers;

final class UserController extends AbstractController
{
	private $userService;

	public function __construct(\Szurubooru\Services\UserService $userService)
	{
		$this->userService = $userService;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/users', [$this, 'create']);
		$router->get('/api/users', [$this, 'getAll']);
		$router->get('/api/users/:id', [$this, 'getById']);
		$router->put('/api/users/:id', [$this, 'update']);
		$router->delete('/api/users/:id', [$this, 'delete']);
	}

	public function create()
	{
		throw new \BadMethodCallException('Not implemented');
	}

	public function update($id)
	{
		throw new \BadMethodCallException('Not implemented');
	}

	public function getAll()
	{
		throw new \BadMethodCallException('Not implemented');
	}

	public function getById($id)
	{
		throw new \BadMethodCallException('Not implemented');
	}

	public function delete($id)
	{
		throw new \BadMethodCallException('Not implemented');
	}
}
