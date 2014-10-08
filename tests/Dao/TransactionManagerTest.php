<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\TokenDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\Token;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class TransactionManagerTest extends AbstractDatabaseTestCase
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
		$token = new Token();
		$token->setName('yo');
		$token->setPurpose(Token::PURPOSE_ACTIVATE);
		return $token;
	}

	private function getTestDao()
	{
		return new TokenDao($this->databaseConnection);
	}

	private function getTransactionManager()
	{
		return new TransactionManager($this->databaseConnection);
	}
}
