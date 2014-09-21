<?php
namespace Szurubooru\Tests\Dao;

class TransactionManagerTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testCommit()
	{
		$testEntity = $this->getTestEntity();
		$testDao = $this->getTestDao();

		$transactionManager = $this->getTransactionManager();
		$transactionManager->commit(function() use ($testDao, &$testEntity)
		{
			$testDao->save($testEntity);
			$this->assertNotNull($testEntity->getId());
		});

		$this->assertNotNull($testEntity->getId());
		$this->assertEntitiesEqual($testEntity, $testDao->findById($testEntity->getId()));
	}

	public function testRollback()
	{
		$testEntity = $this->getTestEntity();
		$testDao = $this->getTestDao();

		$transactionManager = $this->getTransactionManager();
		$transactionManager->rollback(function() use ($testDao, &$testEntity)
		{
			$testDao->save($testEntity);
			$this->assertNotNull($testEntity->getId());
		});

		//ids that could be forged in transaction get left behind after rollback
		$this->assertNotNull($testEntity->getId());

		//but entities shouldn't be saved to database
		$this->assertNull($testDao->findById($testEntity->getId()));
	}

	public function testNestedTransactions()
	{
		$testEntity = $this->getTestEntity();
		$testDao = $this->getTestDao();

		$transactionManager = $this->getTransactionManager();
		$transactionManager->commit(function() use ($transactionManager, $testDao, &$testEntity)
		{
			$transactionManager->commit(function() use ($testDao, &$testEntity)
			{
				$testDao->save($testEntity);
				$this->assertNotNull($testEntity->getId());
			});
		});

		$this->assertNotNull($testEntity->getId());
		$this->assertEntitiesEqual($testEntity, $testDao->findById($testEntity->getId()));
	}

	private function getTestEntity()
	{
		$token = new \Szurubooru\Entities\Token();
		$token->setName('yo');
		$token->setPurpose(\Szurubooru\Entities\Token::PURPOSE_ACTIVATE);
		return $token;
	}

	private function getTestDao()
	{
		return new \Szurubooru\Dao\TokenDao($this->databaseConnection);
	}

	private function getTransactionManager()
	{
		return new \Szurubooru\Dao\TransactionManager($this->databaseConnection);
	}
}
