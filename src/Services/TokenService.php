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
		$transactionFunc = function() use ($tokenName)
		{
			$token = $this->tokenDao->findByName($tokenName);
			if (!$token)
				throw new \InvalidArgumentException('Token with identifier "' . $tokenName . '" not found.');
			return $token;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function invalidateByName($tokenName)
	{
		$transactionFunc = function() use ($tokenName)
		{
			$this->tokenDao->deleteByName($tokenName);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function invalidateByAdditionalData($additionalData)
	{
		$transactionFunc = function() use ($additionalData)
		{
			$this->tokenDao->deleteByAdditionalData($additionalData);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function createAndSaveToken($additionalData, $tokenPurpose)
	{
		$transactionFunc = function() use ($additionalData, $tokenPurpose)
		{
			$token = $this->tokenDao->findByAdditionalDataAndPurpose($additionalData, $tokenPurpose);

			if (!$token)
			{
				$token = new \Szurubooru\Entities\Token();
				$token->setName(sha1(date('r') . uniqid() . microtime(true)));
				$token->setAdditionalData($additionalData);
				$token->setPurpose($tokenPurpose);
				$this->tokenDao->save($token);
			}

			return $token;
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
