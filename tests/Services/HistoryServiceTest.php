<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Dao\SnapshotDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractTestCase;

final class HistoryServiceTest extends AbstractTestCase
{
	private $snapshotDaoMock;
	private $timeServiceMock;
	private $authServiceMock;
	private $transactionManagerMock;

	public static function dataDifferenceProvider()
	{
		yield
		[
			[],
			[],
			['+' => [], '-' => []]
		];

		yield
		[
			['key' => 'unchangedValue'],
			['key' => 'unchangedValue'],
			['+' => [], '-' => []]
		];

		yield
		[
			['key' => 'newValue'],
			[],
			[
				'+' => ['key' => 'newValue'],
				'-' => []
			]
		];

		yield
		[
			[],
			['key' => 'deletedValue'],
			[
				'+' => [],
				'-' => ['key' => 'deletedValue']
			]
		];

		yield
		[
			['key' => 'changedValue'],
			['key' => 'oldValue'],
			[
				'+' => ['key' => 'changedValue'],
				'-' => ['key' => 'oldValue']
			]
		];

		yield
		[
			['key' => []],
			['key' => []],
			[
				'+' => [],
				'-' => []
			]
		];

		yield
		[
			['key' => ['newArrayElement']],
			['key' => []],
			[
				'+' => ['key' => ['newArrayElement']],
				'-' => []
			]
		];

		yield
		[
			['key' => []],
			['key' => ['removedArrayElement']],
			[
				'+' => [],
				'-' => ['key' => ['removedArrayElement']]
			]
		];

		yield
		[
			['key' => ['unchangedArrayElement', 'newArrayElement']],
			['key' => ['unchangedArrayElement', 'oldArrayElement']],
			[
				'+' => ['key' => ['newArrayElement']],
				'-' => ['key' => ['oldArrayElement']]
			]
		];
	}

	public function setUp()
	{
		parent::setUp();
		$this->snapshotDaoMock = $this->mock(SnapshotDao::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->timeServiceMock = $this->mock(TimeService::class);
		$this->authServiceMock = $this->mock(AuthService::class);
	}

	/**
	 * @dataProvider dataDifferenceProvider
	 */
	public function testDataDifference($newData, $oldData, $expectedResult)
	{
		$historyService = $this->getHistoryService();
		$this->assertEquals($expectedResult, $historyService->getDataDifference($newData, $oldData));
	}

	public static function mergingProvider()
	{
		{
			$oldSnapshot = new Snapshot(1);
			$oldSnapshot->setTime(date('c', 1));
			$oldSnapshot->setOperation(Snapshot::OPERATION_CREATION);
			$oldSnapshot->setData(['old' => '1']);

			$newSnapshot = new Snapshot(2);
			$newSnapshot->setTime(date('c', 2));
			$newSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
			$newSnapshot->setData(['new' => '2']);

			$expectedSnapshot = new Snapshot(1);
			$expectedSnapshot->setTime(date('c', 3));
			$expectedSnapshot->setOperation(Snapshot::OPERATION_CREATION);
			$expectedSnapshot->setData(['new' => '2']);
			$expectedSnapshot->setDataDifference(['+' => ['new' => '2'], '-' => []]);

			yield [$oldSnapshot, $newSnapshot, $expectedSnapshot, date('c', 3)];
		}

		{
			$oldSnapshot = new Snapshot(1);
			$oldSnapshot->setOperation(Snapshot::OPERATION_CREATION);
			$oldSnapshot->setData(['old' => '1']);

			$newSnapshot = new Snapshot(2);
			$newSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
			$newSnapshot->setData(['new' => '2']);

			$expectedSnapshot = new Snapshot(2);
			$expectedSnapshot->setTime(date('c', 3000));
			$expectedSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
			$expectedSnapshot->setData(['new' => '2']);
			$expectedSnapshot->setDataDifference(['+' => ['new' => '2'], '-' => ['old' => '1']]);

			yield [$oldSnapshot, $newSnapshot, $expectedSnapshot, date('c', 3000)];
		}

		{
			$oldSnapshot = new Snapshot(1);
			$oldSnapshot->setOperation(Snapshot::OPERATION_CREATION);
			$oldSnapshot->setData(['old' => '1']);
			$oldSnapshot->setUserId(1);

			$newSnapshot = new Snapshot(2);
			$newSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
			$newSnapshot->setData(['new' => '2']);
			$newSnapshot->setUserId(2);

			$expectedSnapshot = new Snapshot(2);
			$expectedSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
			$expectedSnapshot->setData(['new' => '2']);
			$expectedSnapshot->setDataDifference(['+' => ['new' => '2'], '-' => ['old' => '1']]);
			$expectedSnapshot->setUserId(null);

			yield [$oldSnapshot, $newSnapshot, $expectedSnapshot, null];
		}

		{
			$oldSnapshot = new Snapshot(1);
			$oldSnapshot->setOperation(Snapshot::OPERATION_CREATION);
			$oldSnapshot->setData(['old' => '1']);

			$newSnapshot = new Snapshot(2);
			$newSnapshot->setOperation(Snapshot::OPERATION_DELETE);
			$newSnapshot->setData(['new' => '2']);

			$expectedSnapshot = new Snapshot(2);
			$expectedSnapshot->setOperation(Snapshot::OPERATION_DELETE);
			$expectedSnapshot->setData(['new' => '2']);
			$expectedSnapshot->setDataDifference(['+' => ['new' => '2'], '-' => ['old' => '1']]);

			yield [$oldSnapshot, $newSnapshot, $expectedSnapshot, null];
		}
	}

	/**
	 * @dataProvider mergingProvider
	 */
	public function testMerging($oldSnapshot, $newSnapshot, $expectedSnapshot, $currentTime)
	{
		$this->timeServiceMock->method('getCurrentTime')->willReturn($currentTime);

		$this->snapshotDaoMock
			->method('findEarlierSnapshots')
			->will(
				$this->onConsecutiveCalls([$oldSnapshot], null));

		$this->snapshotDaoMock
			->expects($this->once())
			->method('save')
			->will($this->returnCallback(function($param) use (&$actualSnapshot)
				{
					$actualSnapshot = $param;
				}));

		$historyService = $this->getHistoryService();
		$historyService->saveSnapshot($newSnapshot);
		$this->assertEntitiesEqual($expectedSnapshot, $actualSnapshot);
	}

	private function getHistoryService()
	{
		return new HistoryService(
			$this->snapshotDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock,
			$this->authServiceMock);
	}
}
