<?php
namespace Szurubooru\Tests\Dao;

final class TokenDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testRetrievingByValidName()
	{
		$tokenDao = new \Szurubooru\Dao\TokenDao($this->databaseConnection);

		$token = new \Szurubooru\Entities\Token();
		$token->setName('test');
		$token->setPurpose(\Szurubooru\Entities\Token::PURPOSE_LOGIN);

		$tokenDao->save($token);
		$expected = $token;
		$actual = $tokenDao->findByName($token->getName());

		$this->assertEquals($actual, $expected);
	}

	public function testRetrievingByInvalidName()
	{
		$tokenDao = new \Szurubooru\Dao\TokenDao($this->databaseConnection);

		$actual = $tokenDao->findByName('rubbish');

		$this->assertNull($actual);
	}
}
