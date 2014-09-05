<?php
namespace Szurubooru\Dao;

class TokenDao extends AbstractDao
{
	public function __construct(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct($databaseConnection, 'tokens', '\Szurubooru\Entities\Token');
	}

	public function getByName($tokenName)
	{
		$arrayEntity = $this->collection->findOne(['name' => $tokenName]);
		return $this->entityConverter->toEntity($arrayEntity);
	}

	public function deleteByName($tokenName)
	{
		$this->collection->remove(['name' => $tokenName]);
	}

	public function deleteByAdditionalData($additionalData)
	{
		$this->collection->remove(['additionalData' => $additionalData]);
	}
}
