<?php
namespace Szurubooru\Dao;

class UserDao extends AbstractDao implements ICrudDao
{
	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct($databaseConnection, 'users', '\Szurubooru\Entities\User');
	}

	public function findByName($userName)
	{
		$arrayEntity = $this->collection->findOne(['name' => $userName]);
		return $this->entityConverter->toEntity($arrayEntity);
	}

	public function findByEmail($userEmail, $allowUnconfirmed = false)
	{
		$arrayEntity = $this->collection->findOne(['email' => $userEmail]);
		if (!$arrayEntity and $allowUnconfirmed)
			$arrayEntity = $this->collection->findOne(['emailUnconfirmed' => $userEmail]);
		return $this->entityConverter->toEntity($arrayEntity);
	}

	public function hasAnyUsers()
	{
		return (bool) $this->collection->findOne();
	}

	public function deleteByName($userName)
	{
		$this->collection->remove(['name' => $userName]);
		$tokens = $this->db->selectCollection('tokens');
		$tokens->remove(['additionalData' => $userName]);
	}
}
