<?php
namespace Szurubooru\Controllers;

final class AuthController extends AbstractController
{
	private $authService;
	private $userService;
	private $tokenService;
	private $privilegeService;
	private $inputReader;
	private $userViewProxy;
	private $tokenViewProxy;

	public function __construct(
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Services\TokenService $tokenService,
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\UserViewProxy $userViewProxy,
		\Szurubooru\Controllers\ViewProxies\TokenViewProxy $tokenViewProxy)
	{
		$this->authService = $authService;
		$this->userService = $userService;
		$this->tokenService = $tokenService;
		$this->privilegeService = $privilegeService;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
		$this->tokenViewProxy = $tokenViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/login', [$this, 'login']);
		$router->put('/api/login', [$this, 'login']);
	}

	public function login()
	{
		if (isset($this->inputReader->userNameOrEmail) and isset($this->inputReader->password))
		{
			$formData = new \Szurubooru\FormData\LoginFormData($this->inputReader);
			$this->authService->loginFromCredentials($formData);

			$user = $this->authService->getLoggedInUser();
			$this->userService->updateUserLastLoginTime($user);
		}
		elseif (isset($this->inputReader->token))
		{
			$token = $this->tokenService->getByName($this->inputReader->token);
			$this->authService->loginFromToken($token);

			$user = $this->authService->getLoggedInUser();
			$isFromCookie = boolval($this->inputReader->isFromCookie);
			if ($isFromCookie)
				$this->userService->updateUserLastLoginTime($user);
		}
		else
		{
			$this->authService->loginAnonymous();
			$user = $this->authService->getLoggedInUser();
		}

		return
		[
			'token' => $this->tokenViewProxy->fromEntity($this->authService->getLoginToken()),
			'user' => $this->userViewProxy->fromEntity($user),
			'privileges' => $this->privilegeService->getCurrentPrivileges(),
		];
	}
}
