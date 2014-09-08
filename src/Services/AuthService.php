<?php
namespace Szurubooru\Services;

class AuthService
{
	private $loggedInUser = null;
	private $loginToken = null;

	private $validator;
	private $passwordService;
	private $timeService;
	private $userService;
	private $tokenService;

	public function __construct(
		\Szurubooru\Validator $validator,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\TokenService $tokenService,
		\Szurubooru\Services\UserService $userService)
	{
		$this->validator = $validator;
		$this->passwordService = $passwordService;
		$this->timeService = $timeService;
		$this->tokenService = $tokenService;
		$this->userService = $userService;

		$this->loggedInUser = $this->getAnonymousUser();
	}

	public function isLoggedIn()
	{
		return $this->loginToken !== null;
	}

	public function getLoggedInUser()
	{
		return $this->loggedInUser;
	}

	public function getLoginToken()
	{
		return $this->loginToken;
	}

	public function loginFromCredentials($userName, $password)
	{
		$this->validator->validateUserName($userName);
		$this->validator->validatePassword($password);

		$user = $this->userService->getByName($userName);

		$passwordHash = $this->passwordService->getHash($password);
		if ($user->passwordHash != $passwordHash)
			throw new \InvalidArgumentException('Specified password is invalid.');

		$this->loginToken = $this->createAndSaveLoginToken($user);
		$this->loggedInUser = $user;
		$this->userService->updateUserLastLoginTime($user);
	}

	public function loginFromToken($loginTokenName)
	{
		$this->validator->validateToken($loginTokenName);

		$loginToken = $this->tokenService->getByName($loginTokenName);
		if ($loginToken->purpose != \Szurubooru\Entities\Token::PURPOSE_LOGIN)
			throw new \Exception('This token is not a login token.');

		$user = $this->userService->getById($loginToken->additionalData);

		$this->loginToken = $loginToken;
		$this->loggedInUser = $user;
		$this->userService->updateUserLastLoginTime($this->loggedInUser);
	}

	public function getAnonymousUser()
	{
		$user = new \Szurubooru\Entities\User();
		$user->name = 'Anonymous user';
		$user->accessRank = \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS;
		return $user;
	}

	public function loginAnonymous()
	{
		$this->loginToken = null;
		$this->loggedInUser = $this->getAnonymousUser();
	}

	public function logout()
	{
		if (!$this->isLoggedIn())
			throw new \Exception('Not logged in.');

		$this->tokenService->invalidateByToken($this->loginToken);
		$this->loginToken = null;
	}

	private function createAndSaveLoginToken(\Szurubooru\Entities\User $user)
	{
		return $this->tokenService->createAndSaveToken($user, \Szurubooru\Entities\Token::PURPOSE_LOGIN);
	}
}
