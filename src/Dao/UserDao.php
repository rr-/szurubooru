<?php
namespace Szurubooru\Dao;

class UserDao extends AbstractDao implements ICrudDao
{
	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct($databaseConnection, 'users', \Szurubooru\Entities\User::class);
	}

	public function findByName($userName)
	{
		return $this->findOneBy('name', $userName);
	}

	public function findByEmail($userEmail, $allowUnconfirmed = false)
	{
		$result = $this->findOneBy('email', $userEmail);
		if (!$result and $allowUnconfirmed)
		{
			$result = $this->findOneBy('emailUnconfirmed', $userEmail);
		}
		return $result;
	}

	public function hasAnyUsers()
	{
		return $this->hasAnyRecords();
	}

	public function deleteByName($userName)
	{
		$this->deleteBy('name', $userName);
		$this->fpdo->deleteFrom('tokens')->where('additionalData', $userName);
	}
}
