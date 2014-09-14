<?php
namespace Szurubooru\Dao;

class TokenDao extends AbstractDao
{
	public function __construct(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct($databaseConnection, 'tokens', \Szurubooru\Entities\Token::class);
	}

	public function findByName($tokenName)
	{
		return $this->findOneBy('name', $tokenName);
	}

	public function deleteByName($tokenName)
	{
		return $this->deleteBy('name', $tokenName);
	}

	public function deleteByAdditionalData($additionalData)
	{
		return $this->deleteBy('additionalData', $additionalData);
	}
}
