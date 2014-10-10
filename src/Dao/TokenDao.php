<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\TokenEntityConverter;
use Szurubooru\DatabaseConnection;

class TokenDao extends AbstractDao
{
	public function __construct(DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'tokens',
			new TokenEntityConverter());
	}

	public function findByName($tokenName)
	{
		return $this->findOneBy('name', $tokenName);
	}

	public function findByAdditionalDataAndPurpose($additionalData, $purpose)
	{
		$query = $this->fpdo->from($this->tableName)
			->where('additionalData', $additionalData)
			->where('purpose', $purpose);
		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		if (!$entities or !count($entities))
			return null;
		$entity = array_shift($entities);
		return $entity;
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
