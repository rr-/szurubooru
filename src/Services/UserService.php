<?php
namespace Szurubooru\Services;

class UserService
{
	private $userDao;

	public function __construct(\Szurubooru\Dao\UserDao $userDao)
	{
		$this->userDao = $userDao;
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
}
