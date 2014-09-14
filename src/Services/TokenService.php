<?php
namespace Szurubooru\Services;

class TokenService
{
	private $transactionManager;
	private $tokenDao;

	public function __construct(
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Dao\TokenDao $tokenDao)
	{
		$this->transactionManager = $transactionManager;
		$this->tokenDao = $tokenDao;
	}

	public function getByName($tokenName)
	{
		return $this->transactionManager->rollback(function() use ($tokenName)
		{
			$token = $this->tokenDao->findByName($tokenName);
			if (!$token)
				throw new \InvalidArgumentException('Token with identifier "' . $tokenName . '" not found.');
			return $token;
		});
	}

	public function invalidateByName($tokenName)
	{
		$this->transactionManager->commit(function() use ($tokenName)
		{
			$this->tokenDao->deleteByName($tokenName);
		});
	}

	public function invalidateByAdditionalData($additionalData)
	{
		$this->transactionManager->commit(function() use ($additionalData)
		{
			$this->tokenDao->deleteByAdditionalData($additionalData);
		});
	}

	public function createAndSaveToken($additionalData, $tokenPurpose)
	{
		return $this->transactionManager->commit(function() use ($additionalData, $tokenPurpose)
		{
			$token = new \Szurubooru\Entities\Token();
			$token->setName(sha1(date('r') . uniqid() . microtime(true)));
			$token->setAdditionalData($additionalData);
			$token->setPurpose($tokenPurpose);
			$this->invalidateByAdditionalData($additionalData);
			$this->tokenDao->save($token);
			return $token;
		});
	}
}
