<?php
namespace Szurubooru\Dao;

class UserDao extends AbstractDao implements ICrudDao
{
	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct($databaseConnection, 'users', '\Szurubooru\Entities\User');
	}

	public function getByName($userName)
	{
		$arrayEntity = $this->collection->findOne(['name' => $userName]);
		return $this->entityConverter->toEntity($arrayEntity);
	}

	public function hasAnyUsers()
	{
		return (bool) $this->collection->findOne();
	}
}
