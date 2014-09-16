<?php
namespace Szurubooru\Tests\Dao;

final class UserDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testRetrievingByValidName()
	{
		$userDao = $this->getUserDao();

		$user = $this->getTestUser();
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

		$user = $this->getTestUser();
		$userDao->save($user);
		$this->assertTrue($userDao->hasAnyUsers());
	}

	private function getUserDao()
	{
		return new \Szurubooru\Dao\UserDao($this->databaseConnection);
	}

	private function getTestUser()
	{
		$user = new \Szurubooru\Entities\User();
		$user->setName('test');
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime('whatever');
		$user->setRegistrationTime('whatever');
		$user->setAccessRank(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
