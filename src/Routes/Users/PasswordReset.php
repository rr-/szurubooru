<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Services\UserService;

class PasswordReset extends AbstractUserRoute
{
	public function __construct(UserService $userService)
	{
		$this->userService = $userService;
	}

	public function getMethods()
	{
		return ['POST', 'PUT'];
	}

	public function getUrl()
	{
		return '/api/password-reset/:userNameOrEmail';
	}

	public function work()
	{
		$user = $this->userService->getByNameOrEmail($this->getArgument('userNameOrEmail'));
		return $this->userService->sendPasswordResetEmail($user);
	}
}
