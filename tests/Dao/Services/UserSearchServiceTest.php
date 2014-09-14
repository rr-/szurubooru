<?php
namespace Szurubooru\Tests\Dao\Services;

class UserSearchServiceTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $userDao;

	public function setUp()
	{
		parent::setUp();
		$this->userDao = new \Szurubooru\Dao\UserDao($this->databaseConnection);
	}

	public function testNothing()
	{
		$searchFilter = new \Szurubooru\Dao\SearchFilter(1);
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [], 0);

		$userSearchService = $this->getUserSearchService();
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);
	}

	public function testSorting()
	{
		$user1 = $this->getTestUser('reginald');
		$user2 = $this->getTestUser('beartato');
		$user1->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user2->setRegistrationTime(date('c', mktime(1, 2, 3)));

		$this->userDao->save($user1);
		$this->userDao->save($user2);

		$userSearchService = $this->getUserSearchService();
		$searchFilter = new \Szurubooru\Dao\SearchFilter(1);
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [$user2], 2);
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);

		$searchFilter->order = 'name,asc';
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [$user2], 2);
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);

		$searchFilter->order = 'name,desc';
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [$user1], 2);
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);

		$searchFilter->order = 'registrationTime,desc';
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [$user1], 2);
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);

		$searchFilter->order = 'registrationTime';
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [$user2], 2);
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);
	}

	private function getUserSearchService()
	{
		return new \Szurubooru\Dao\Services\UserSearchService($this->databaseConnection, $this->userDao);
	}

	private function getTestUser($userName)
	{
		$user = new \Szurubooru\Entities\User();
		$user->setName($userName);
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime('whatever');
		$user->setRegistrationTime('whatever');
		$user->setAccessRank('whatever');
		return $user;
	}
}
