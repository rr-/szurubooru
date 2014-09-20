<?php
namespace Szurubooru\Tests\Dao\Services;

class UserSearchServiceTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $userDao;

	public function setUp()
	{
		parent::setUp();

		$fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
		$this->userDao = new \Szurubooru\Dao\UserDao(
			$this->databaseConnection,
			$fileServiceMock,
			$thumbnailServiceMock);
	}

	public function testNothing()
	{
		$searchFilter = new \Szurubooru\Dao\SearchFilter(1);
		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, [], 0);

		$userSearchService = $this->getUserSearchService();
		$actual = $userSearchService->getFiltered($searchFilter);
		$this->assertEquals($expected, $actual);
	}

	public function testDefaultOrder()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting(null, [$user2]);
	}

	public function testOrderByNameAscending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting('name,asc', [$user1]);
	}

	public function testOrderByNameDescending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting('name,desc', [$user2]);
	}

	public function testOrderByRegistrationTimeAscending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting('registrationTime,asc', [$user2]);
	}

	public function testOrderByRegistrationTimeDescending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting('registrationTime,desc', [$user1]);
	}

	private function prepareUsers()
	{
		$user1 = $this->getTestUser('beartato');
		$user2 = $this->getTestUser('reginald');
		$user1->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user2->setRegistrationTime(date('c', mktime(1, 2, 3)));

		$this->userDao->save($user1);
		$this->userDao->save($user2);
		return [$user1, $user2];
	}

	private function doTestSorting($order, $expectedUsers)
	{
		$userSearchService = $this->getUserSearchService();
		$searchFilter = new \Szurubooru\Dao\SearchFilter(1);
		if ($order !== null)
			$searchFilter->order = $order;

		$expected = new \Szurubooru\Dao\SearchResult($searchFilter, $expectedUsers, 2);
		$actual = $userSearchService->getFiltered($searchFilter);
		foreach ($actual->entities as $entity)
			$entity->resetLazyLoaders();
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
		$user->setAccessRank(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
