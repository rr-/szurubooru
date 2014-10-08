<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\SnapshotDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\User;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class SnapshotDaoTest extends AbstractDatabaseTestCase
{
	public function setUp()
	{
		parent::setUp();
		$this->userDaoMock = $this->mock(UserDao::class);
	}

	public function testSaving()
	{
		$snapshot = $this->getTestSnapshot();
		$snapshotDao = $this->getSnapshotDao();
		$snapshotDao->save($snapshot);
		$this->assertNotNull($snapshot->getId());
		$this->assertEntitiesEqual($snapshot, $snapshotDao->findById($snapshot->getId()));
	}

	public function testUserLazyLoader()
	{
		$snapshot = $this->getTestSnapshot();
		$snapshot->setUser(new User(5));
		$this->assertEquals(5, $snapshot->getUserId());
		$snapshotDao = $this->getSnapshotDao();
		$snapshotDao->save($snapshot);
		$savedSnapshot = $snapshotDao->findById($snapshot->getId());
		$this->assertEquals(5, $savedSnapshot->getUserId());

		$this->userDaoMock
			->expects($this->once())
			->method('findById');
		$savedSnapshot->getUser();
	}

	private function getTestSnapshot()
	{
		$snapshot = new Snapshot();
		$snapshot->setType(Snapshot::TYPE_POST);
		$snapshot->setData(['wake up', 'neo', ['follow' => 'white rabbit']]);
		$snapshot->setPrimaryKey(1);
		$snapshot->setTime(date('c', mktime(1, 2, 3)));
		$snapshot->setUserId(null);
		$snapshot->setOperation(Snapshot::OPERATION_CHANGE);
		return $snapshot;
	}

	private function getSnapshotDao()
	{
		return new SnapshotDao(
			$this->databaseConnection,
			$this->userDaoMock);
	}
}
