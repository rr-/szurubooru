<?php
namespace Szurubooru\Services;

class TokenService
{
	private $tokenDao;

	public function __construct(\Szurubooru\Dao\TokenDao $tokenDao)
	{
		$this->tokenDao = $tokenDao;
	}

	public function getByName($tokenName)
	{
		$token = $this->tokenDao->findByName($tokenName);
		if (!$token)
			throw new \InvalidArgumentException('Token with identifier "' . $tokenName . '" not found.');
		return $token;
	}

	public function invalidateByName($tokenName)
	{
		return $this->tokenDao->deleteByName($tokenName);
	}

	public function invalidateByAdditionalData($additionalData)
	{
		return $this->tokenDao->deleteByAdditionalData($additionalData);
	}

	public function createAndSaveToken($additionalData, $tokenPurpose)
	{
		$token = new \Szurubooru\Entities\Token();
		$token->setName(sha1(date('r') . uniqid() . microtime(true)));
		$token->setAdditionalData($additionalData);
		$token->setPurpose($tokenPurpose);
		$this->invalidateByAdditionalData($additionalData);
		$this->tokenDao->save($token);
		return $token;
	}
}
