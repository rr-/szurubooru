<?php
namespace Szurubooru\Tests\Dao;

final class UserDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testRetrievingByValidName()
	{
		$userDao = new \Szurubooru\Dao\UserDao($this->databaseConnection);

		$user = new \Szurubooru\Entities\User();
		$user->name = 'test';

		$userDao->save($user);
		$expected = $user;
		$actual = $userDao->getByName($user->name);

		$this->assertEquals($actual, $expected);
	}

	public function testRetrievingByInvalidName()
	{
		$userDao = new \Szurubooru\Dao\UserDao($this->databaseConnection);

		$actual = $userDao->getByName('rubbish');

		$this->assertNull($actual);
	}

	public function testCheckingUserPresence()
	{
		$userDao = new \Szurubooru\Dao\UserDao($this->databaseConnection);

		$this->assertFalse($userDao->hasAnyUsers());

		$user = new \Szurubooru\Entities\User();
		$user->name = 'test';
		$userDao->save($user);

		$this->assertTrue($userDao->hasAnyUsers());
	}
}
