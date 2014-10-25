<?php
namespace Szurubooru\Tests\Services;
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

	public static function snapshotDataDifferenceProvider()
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
				'+' => [['key', 'newValue']],
				'-' => []
			]
		];

		yield
		[
			[],
			['key' => 'deletedValue'],
			[
				'+' => [],
				'-' => [['key', 'deletedValue']]
			]
		];

		yield
		[
			['key' => 'changedValue'],
			['key' => 'oldValue'],
			[
				'+' => [['key', 'changedValue']],
				'-' => [['key', 'oldValue']]
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
				'+' => [['key', 'newArrayElement']],
				'-' => []
			]
		];

		yield
		[
			['key' => []],
			['key' => ['removedArrayElement']],
			[
				'+' => [],
				'-' => [['key', 'removedArrayElement']]
			]
		];

		yield
		[
			['key' => ['unchangedValue', 'newValue']],
			['key' => ['unchangedValue', 'oldValue']],
			[
				'+' => [['key', 'newValue']],
				'-' => [['key', 'oldValue']]
			]
		];
	}

	public function setUp()
	{
		parent::setUp();
		$this->snapshotDaoMock = $this->mock(SnapshotDao::class);
		$this->transactionManagerMock = $this->mock(TransactionManager::class);
		$this->timeServiceMock = $this->mock(TimeService::class);
		$this->authServiceMock = $this->mock(AuthService::class);
	}

	/**
	 * @dataProvider snapshotDataDifferenceProvider
	 */
	public function testSnapshotDataDifference($newData, $oldData, $expectedResult)
	{
		$historyService = $this->getHistoryService();
		$this->assertEquals($expectedResult, $historyService->getSnapshotDataDifference($newData, $oldData));
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
