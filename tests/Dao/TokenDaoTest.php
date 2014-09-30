<?php
namespace Szurubooru\Tests\Dao;

final class TokenDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testRetrievingByValidName()
	{
		$token = new \Szurubooru\Entities\Token();
		$token->setName('test');
		$token->setPurpose(\Szurubooru\Entities\Token::PURPOSE_LOGIN);

		$tokenDao = $this->getTokenDao();
		$tokenDao->save($token);
		$expected = $token;
		$actual = $tokenDao->findByName($token->getName());

		$this->assertEntitiesEqual($actual, $expected);
	}

	public function testRetrievingByInvalidName()
	{
		$tokenDao = $this->getTokenDao();
		$actual = $tokenDao->findByName('rubbish');

		$this->assertNull($actual);
	}

	public function testRetrievingByAdditionalDataAndPurpose()
	{
		$token = new \Szurubooru\Entities\Token();
		$token->setName('test');
		$token->setPurpose(\Szurubooru\Entities\Token::PURPOSE_LOGIN);

		$tokenDao = $this->getTokenDao();
		$tokenDao->save($token);
		$expected = $token;

		$this->assertEntitiesEqual($expected, $tokenDao->findByAdditionalDataAndPurpose(null, \Szurubooru\Entities\Token::PURPOSE_LOGIN));
		$this->assertNull($tokenDao->findByAdditionalDataAndPurpose(null, \Szurubooru\Entities\Token::PURPOSE_ACTIVATE));
	}

	private function getTokenDao()
	{
		return new \Szurubooru\Dao\TokenDao($this->databaseConnection);
	}
}
