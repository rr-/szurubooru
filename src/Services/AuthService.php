<?php
namespace Szurubooru\Services;

final class AuthService
{
	private $loggedInUser = null;
	private $loginToken = null;

	private $passwordService;
	private $userDao;
	private $tokenDao;

	public function __construct(
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Dao\TokenDao $tokenDao,
		\Szurubooru\Dao\UserDao $userDao)
	{
		$this->loggedInUser = new \Szurubooru\Entities\User();
		$this->loggedInUser->name = 'Anonymous';
		$this->userDao = $userDao;
		$this->tokenDao = $tokenDao;
		$this->passwordService = $passwordService;
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
		return $this->token;
	}

	public function loginFromCredentials($userName, $password)
	{
		$user = $this->userDao->getByName($userName);
		if (!$user)
			throw new \InvalidArgumentException('User not found.');

		$passwordHash = $this->passwordService->getHash($password);
		if ($user->passwordHash != $passwordHash)
			throw new \InvalidArgumentException('Specified password is invalid.');

		$this->loggedInUser = $user;
		$this->loginToken = $this->createAndSaveLoginToken($user);
	}

	public function loginFromToken($loginTokenName)
	{
		$loginToken = $this->tokenDao->getByName($loginTokenName);
		if (!$loginToken)
			throw new \Exception('Error while logging in (invalid token.)');

		$this->loginToken = $loginToken;
		$this->loggedInUser = $this->userDao->getById($loginToken->additionalData);
		if (!$this->loggedInUser)
		{
			$this->logout();
			throw new \RuntimeException('Token is correct, but user is not. Have you deleted your account?');
		}
	}

	public function logout()
	{
		if (!$this->isLoggedIn())
			throw new \Exception('Not logged in.');

		$this->tokenDao->deleteByName($this->loginToken);
		$this->loginToken = null;
	}

	private function createAndSaveLoginToken(\Szurubooru\Entities\User $user)
	{
		$loginToken = new \Szurubooru\Entities\Token();
		$loginToken->name = hash('sha256', $user->name . '/' . microtime(true));
		$loginToken->additionalData = $user->id;
		$loginToken->purpose = \Szurubooru\Entities\Token::PURPOSE_LOGIN;
		$this->tokenDao->save($loginToken);
		return $loginToken;
	}
}
