<?php
namespace Szurubooru\Tests\Dao;

final class TokenDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testRetrievingByValidName()
	{
		$tokenDao = new \Szurubooru\Dao\TokenDao($this->databaseConnection);

		$token = new \Szurubooru\Entities\Token();
		$token->name = 'test';

		$tokenDao->save($token);
		$expected = $token;
		$actual = $tokenDao->getByName($token->name);

		$this->assertEquals($actual, $expected);
	}

	public function testRetrievingByInvalidName()
	{
		$tokenDao = new \Szurubooru\Dao\TokenDao($this->databaseConnection);

		$actual = $tokenDao->getByName('rubbish');

		$this->assertNull($actual);
	}
}
