<?php
namespace Szurubooru\Services;

class AuthService
{
	private $loggedInUser = null;
	private $loginToken = null;

	private $validator;
	private $config;
	private $passwordService;
	private $timeService;
	private $userDao;
	private $tokenDao;

	public function __construct(
		\Szurubooru\Validator $validator,
		\Szurubooru\Config $config,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Dao\TokenDao $tokenDao,
		\Szurubooru\Dao\UserDao $userDao)
	{
		$this->loggedInUser = $this->getAnonymousUser();

		$this->validator = $validator;
		$this->config = $config;
		$this->passwordService = $passwordService;
		$this->timeService = $timeService;
		$this->tokenDao = $tokenDao;
		$this->userDao = $userDao;
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

		$user = $this->userDao->getByName($userName);
		if (!$user)
			throw new \InvalidArgumentException('User not found.');

		$passwordHash = $this->passwordService->getHash($password);
		if ($user->passwordHash != $passwordHash)
			throw new \InvalidArgumentException('Specified password is invalid.');

		$this->loginToken = $this->createAndSaveLoginToken($user);
		$this->loggedInUser = $user;
		$this->updateLoginTime($user);
	}

	public function loginFromToken($loginTokenName)
	{
		$this->validator->validateToken($loginTokenName);

		$loginToken = $this->tokenDao->getByName($loginTokenName);
		if (!$loginToken)
			throw new \Exception('Invalid login token.');

		$this->loginToken = $loginToken;
		$this->loggedInUser = $this->userDao->getById($loginToken->additionalData);
		if (!$this->loggedInUser)
			throw new \Exception('User was deleted.');
		$this->updateLoginTime($this->loggedInUser);

		if (!$this->loggedInUser)
		{
			$this->logout();
			throw new \RuntimeException('Token is correct, but user is not. Have you deleted your account?');
		}
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

		$this->tokenDao->deleteByName($this->loginToken);
		$this->loginToken = null;
	}

	public function getCurrentPrivileges()
	{
		switch ($this->getLoggedInUser()->accessRank)
		{
			case \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS: $keyName = 'anonymous'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER: $keyName = 'regularUser'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_POWER_USER: $keyName = 'powerUser'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_MODERATOR: $keyName = 'moderator'; break;
			case \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR: $keyName = 'administrator'; break;
			default:
				throw new \DomainException('Invalid access rank!');
		}
		return array_filter(preg_split('/[;,\s]+/', $this->config->security->privileges[$keyName]));
	}

	public function hasPrivilege($privilege)
	{
		return in_array($privilege, $this->getCurrentPrivileges());
	}

	public function assertPrivilege($privilege)
	{
		if (!$this->hasPrivilege($privilege))
			throw new \DomainException('Unprivileged operation');
	}

	private function createAndSaveLoginToken(\Szurubooru\Entities\User $user)
	{
		$loginToken = new \Szurubooru\Entities\Token();
		$loginToken->name = hash('sha256', $user->name . '/' . microtime(true));
		$loginToken->additionalData = $user->id;
		$loginToken->purpose = \Szurubooru\Entities\Token::PURPOSE_LOGIN;
		$this->tokenDao->deleteByAdditionalData($loginToken->additionalData);
		$this->tokenDao->save($loginToken);
		return $loginToken;
	}

	private function updateLoginTime($user)
	{
		$user->lastLoginTime = $this->timeService->getCurrentTime();
		$this->userDao->save($user);
	}
}
