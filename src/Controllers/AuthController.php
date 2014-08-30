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
		$router->put('/api/login', [$this, 'login']);
	}

	public function login()
	{
		$input = new \Szurubooru\Helpers\InputReader();

		if ($input->userName and $input->password)
			$this->authService->loginFromCredentials($input->userName, $input->password);
		elseif ($input->token)
			$this->authService->loginFromToken($input->token);
		else
			throw new \Szurubooru\MissingArgumentException();

		return [
			'token' => $this->authService->getLoginToken(),
			'user' => $this->authService->getLoggedInUser()
		];
	}
}
