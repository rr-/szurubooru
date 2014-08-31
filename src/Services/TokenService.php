<?php
namespace Szurubooru\Services;

class TokenService
{
	private $tokenDao;

	public function __construct(\Szurubooru\Dao\TokenDao $tokenDao)
	{
		$this->tokenDao = $tokenDao;
	}

	public function getById($tokenId)
	{
		return $this->tokenDao->getById($tokenId);
	}

	public function getByName($tokenName)
	{
		return $this->tokenDao->getByName($tokenName);
	}

	public function deleteByName($tokenName)
	{
		return $this->tokenDao->deleteByName($tokenName);
	}

	public function save($token)
	{
		return $this->tokenDao->save($token);
	}
}
