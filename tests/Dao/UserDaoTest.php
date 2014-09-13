<?php
namespace Szurubooru\Tests\Dao;

final class UserDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testRetrievingByValidName()
	{
		$userDao = $this->getUserDao();

		$user = new \Szurubooru\Entities\User();
		$user->setName('test');

		$userDao->save($user);
		$expected = $user;
		$actual = $userDao->findByName($user->getName());

		$this->assertEquals($actual, $expected);
	}

	public function testRetrievingByInvalidName()
	{
		$userDao = $this->getUserDao();

		$actual = $userDao->findByName('rubbish');

		$this->assertNull($actual);
	}

	public function testCheckingUserPresence()
	{
		$userDao = $this->getUserDao();

		$this->assertFalse($userDao->hasAnyUsers());

		$user = new \Szurubooru\Entities\User();
		$user->setName('test');
		$userDao->save($user);

		$this->assertTrue($userDao->hasAnyUsers());
	}

	private function getUserDao()
	{
		return new \Szurubooru\Dao\UserDao($this->databaseConnection);
	}
}
