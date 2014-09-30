<?php
namespace Szurubooru\Tests\Dao;

class UserDaoFilterTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $fileServiceMock;
	private $thumbnailServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
	}

	public function pagingProvider()
	{
		$allUsers = ['xena', 'gabrielle'];
		list ($user1, $user2) = $allUsers;
		return [
			[1, 1, [$user1], $allUsers],
			[1, 2, [$user1, $user2], $allUsers],
			[2, 1, [$user2], $allUsers],
			[2, 2, [], $allUsers],
		];
	}

	public function testNothing()
	{
		$searchFilter = new \Szurubooru\SearchServices\Filters\UserFilter();
		$searchFilter->setPageNumber(1);
		$searchFilter->setPageSize(2);
		$userDao = $this->getUserDao();
		$result = $userDao->findFiltered($searchFilter);
		$this->assertEmpty($result->getEntities());
		$this->assertEquals(0, $result->getTotalRecords());
		$this->assertEquals(1, $result->getPageNumber());
		$this->assertEquals(2, $result->getPageSize());
	}

	/**
	 * @dataProvider pagingProvider
	 */
	public function testPaging($pageNumber, $pageSize, $expectedUserNames, $allUserNames)
	{
		$userDao = $this->getUserDao();
		$expectedUsers = [];
		foreach ($allUserNames as $userName)
		{
			$user = self::getTestUser($userName);
			$userDao->save($user);
			if (in_array($userName, $expectedUserNames))
				$expectedUsers[] = $user;
		}

		$searchFilter = new \Szurubooru\SearchServices\Filters\UserFilter();
		$searchFilter->setOrder([
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_NAME =>
				\Szurubooru\SearchServices\Filters\UserFilter::ORDER_DESC]);
		$searchFilter->setPageNumber($pageNumber);
		$searchFilter->setPageSize($pageSize);

		$result = $userDao->findFiltered($searchFilter);
		$this->assertEquals(count($allUserNames), $result->getTotalRecords());
		$this->assertEquals($pageNumber, $result->getPageNumber());
		$this->assertEquals($pageSize, $result->getPageSize());
		$this->assertEntitiesEqual($expectedUsers, array_values($result->getEntities()));
	}

	public function testDefaultOrder()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting(null, null, [$user1, $user2]);
	}

	public function testOrderByNameAscending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting(
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_NAME,
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_ASC,
			[$user1, $user2]);
	}

	public function testOrderByNameDescending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting(
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_NAME,
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_DESC,
			[$user2, $user1]);
	}

	public function testOrderByRegistrationTimeAscending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting(
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_REGISTRATION_TIME,
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_ASC,
			[$user2, $user1]);
	}

	public function testOrderByRegistrationTimeDescending()
	{
		list ($user1, $user2) = $this->prepareUsers();
		$this->doTestSorting(
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_REGISTRATION_TIME,
			\Szurubooru\SearchServices\Filters\UserFilter::ORDER_DESC,
			[$user1, $user2]);
	}

	private function prepareUsers()
	{
		$user1 = self::getTestUser('beartato');
		$user2 = self::getTestUser('reginald');
		$user1->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user2->setRegistrationTime(date('c', mktime(1, 2, 3)));

		$userDao = $this->getUserDao();
		$userDao->save($user1);
		$userDao->save($user2);
		return [$user1, $user2];
	}

	private function doTestSorting($order, $orderDirection, $expectedUsers)
	{
		$userDao = $this->getUserDao();
		$searchFilter = new \Szurubooru\SearchServices\Filters\UserFilter();
		if ($order !== null)
			$searchFilter->setOrder([$order => $orderDirection]);

		$result = $userDao->findFiltered($searchFilter, 1, 10);
		$this->assertInstanceOf(\Szurubooru\SearchServices\Result::class, $result);
		$this->assertEquals($searchFilter, $result->getSearchFilter());
		$this->assertEntitiesEqual(array_values($expectedUsers), array_values($result->getEntities()));
		$this->assertEquals(count($expectedUsers), $result->getTotalRecords());
		$this->assertNull($result->getPageNumber());
		$this->assertNull($result->getPageSize());
	}

	private function getUserDao()
	{
		return new \Szurubooru\Dao\UserDao(
			$this->databaseConnection,
			$this->fileServiceMock,
			$this->thumbnailServiceMock);
	}

	private static function getTestUser($userName)
	{
		$user = new \Szurubooru\Entities\User();
		$user->setName($userName);
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime(date('c', mktime(1, 2, 3)));
		$user->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user->setAccessRank(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
