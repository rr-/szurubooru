<?php
namespace Szurubooru\Services;

class AuthService
{
	private $loggedInUser = null;
	private $loginToken = null;

	private $config;
	private $passwordService;
	private $timeService;
	private $userService;
	private $tokenService;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\TokenService $tokenService,
		\Szurubooru\Services\UserService $userService)
	{
		$this->config = $config;
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

	public function loginFromCredentials($formData)
	{
		$user = $this->userService->getByNameOrEmail($formData->userNameOrEmail);
		$this->doFinalChecksOnUser($user);

		$hashValid = $this->passwordService->isHashValid(
			$formData->password,
			$user->getPasswordSalt(),
			$user->getPasswordHash());

		if (!$hashValid)
			throw new \InvalidArgumentException('Specified password is invalid.');

		$this->loginToken = $this->createAndSaveLoginToken($user);
		$this->loggedInUser = $user;
	}

	public function loginFromToken(\Szurubooru\Entities\Token $token)
	{
		if ($token->getPurpose() !== \Szurubooru\Entities\Token::PURPOSE_LOGIN)
			throw new \Exception('This token is not a login token.');

		$user = $this->userService->getById($token->getAdditionalData());
		$this->doFinalChecksOnUser($user);

		$this->loginToken = $token;
		$this->loggedInUser = $user;
	}

	public function getAnonymousUser()
	{
		$user = new \Szurubooru\Entities\User();
		$user->setName('Anonymous user');
		$user->setAccessRank(\Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS);
		$user->setAvatarStyle(\Szurubooru\Entities\User::AVATAR_STYLE_BLANK);
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

		$this->tokenService->invalidateByName($this->loginToken);
		$this->loginToken = null;
	}

	private function createAndSaveLoginToken(\Szurubooru\Entities\User $user)
	{
		return $this->tokenService->createAndSaveToken($user->getId(), \Szurubooru\Entities\Token::PURPOSE_LOGIN);
	}

	private function doFinalChecksOnUser($user)
	{
		if (!$user->isAccountConfirmed() and $this->config->security->needEmailActivationToRegister)
			throw new \DomainException('User didn\'t confirm account yet.');

		if ($user->isBanned())
			throw new \DomainException('Banned!');
	}
}
