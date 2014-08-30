<?php
namespace Szurubooru\Controllers;

final class AuthController extends AbstractController
{
	private $authService;

	public function __construct(\Szurubooru\Services\AuthService $authService)
	{
		$this->authService = $authService;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/login', [$this, 'login']);
		$router->get('/api/login', [$this, 'login']);
	}

	public function login()
	{
		$input = new \Szurubooru\Helpers\InputReader();
		$this->authService->loginFromCredentials($input->userName, $input->password);
		return $this->authService->getLoginToken();
	}
}
