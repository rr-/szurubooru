<?php
namespace Szurubooru\Controllers;

final class AuthController extends AbstractController
{
	private $authService;
	private $inputReader;

	public function __construct(
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->authService = $authService;
		$this->inputReader = $inputReader;

	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/login', [$this, 'login']);
		$router->put('/api/login', [$this, 'login']);
	}

	public function login()
	{
		if (isset($this->inputReader->userName) and isset($this->inputReader->password))
		{
			if (!$this->inputReader->userName)
				throw new \DomainException('User name cannot be empty.');
			else if (!$this->inputReader->password)
				throw new \DomainException('Password cannot be empty.');

			$this->authService->loginFromCredentials($this->inputReader->userName, $this->inputReader->password);
		}
		elseif (isset($this->inputReader->token))
		{
			if (!$this->inputReader->token)
				throw new \DomainException('Authentication token cannot be empty.');
			$this->authService->loginFromToken($this->inputReader->token);
		}
		else
		{
			$this->authService->loginAnonymous();
		}

		return
		[
			'token' => new \Szurubooru\ViewProxies\Token($this->authService->getLoginToken()),
			'user' => new \Szurubooru\ViewProxies\User($this->authService->getLoggedInUser()),
		];
	}
}
