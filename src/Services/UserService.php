<?php
namespace Szurubooru\Services;

class UserService
{
	private $userDao;
	private $config;

	public function __construct(
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Config $config)
	{
		$this->userDao = $userDao;
		$this->config = $config;
	}

	public function getById($userId)
	{
		return $this->userDao->getById($userId);
	}

	public function getByName($userName)
	{
		return $this->userDao->getByName($userName);
	}

	public function save($user)
	{
		return $this->userDao->save($user);
	}

	public function validateUserName($userName)
	{
		if (!$userName)
			throw new \DomainException('User name cannot be empty.');

		$minUserNameLength = intval($this->config->users->minUserNameLength);
		$maxUserNameLength = intval($this->config->users->maxserNameLength);
		if (strlen($userName) < $minUserNameLength)
			throw new \DomainException('User name must have at least ' . $minUserNameLength . ' character(s).');
		if (strlen($userName) < $maxUserNameLength)
			throw new \DomainException('User name must have at most ' . $minUserNameLength . ' character(s).');
	}

	public function getAnonymousUser()
	{
		$user = new \Szurubooru\Entities\User();
		$user->name = 'Anonymous user';
		$user->accessRank = \Szurubooru\Entities\User::ACCESS_RANK_ANONYMOUS;
	}
}
