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
            //basic merging
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

            yield [[$oldSnapshot], $newSnapshot, $expectedSnapshot, date('c', 3), [2]];
        }

        {
            //too big time gap for merge
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

            yield [[$oldSnapshot], $newSnapshot, $expectedSnapshot, date('c', 3000), []];
        }

        {
            //operations done by different user shouldn't be merged
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

            yield [[$oldSnapshot], $newSnapshot, $expectedSnapshot, null, []];
        }

        {
            //merge that leaves only delete snapshot should be removed altogether
            $oldSnapshot = new Snapshot(1);
            $oldSnapshot->setOperation(Snapshot::OPERATION_CREATION);
            $oldSnapshot->setData(['old' => '1']);

            $newSnapshot = new Snapshot(2);
            $newSnapshot->setOperation(Snapshot::OPERATION_DELETE);
            $newSnapshot->setData(['new' => '2']);

            yield [[$oldSnapshot], $newSnapshot, null, null, [2, 1]];
        }

        {
            //chaining to creation snapshot should preserve operation type
            $oldestSnapshot = new Snapshot(1);
            $oldestSnapshot->setOperation(Snapshot::OPERATION_CREATION);
            $oldestSnapshot->setData(['oldest' => '0']);

            $oldSnapshot = new Snapshot(2);
            $oldSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
            $oldSnapshot->setData(['old' => '1']);

            $newSnapshot = new Snapshot(3);
            $newSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
            $newSnapshot->setData(['oldest' => '0', 'new' => '2']);

            $expectedSnapshot = new Snapshot(1);
            $expectedSnapshot->setOperation(Snapshot::OPERATION_CREATION);
            $expectedSnapshot->setData(['oldest' => '0', 'new' => '2']);
            $expectedSnapshot->setDataDifference(['+' => ['oldest' => '0', 'new' => '2'], '-' => []]);

            yield [[$oldSnapshot, $oldestSnapshot], $newSnapshot, $expectedSnapshot, null, [3, 2]];

            $newSnapshot = clone($newSnapshot);
            $newSnapshot->setId(null);
            yield [[$oldSnapshot, $oldestSnapshot], $newSnapshot, $expectedSnapshot, null, [2]];
        }

        {
            //chaining to edit snapshot should update operation type
            $oldestSnapshot = new Snapshot(1);
            $oldestSnapshot->setOperation(Snapshot::OPERATION_CREATION);
            $oldestSnapshot->setData(['oldest' => '0']);
            $oldestSnapshot->setTime(date('c', 1));

            $oldSnapshot = new Snapshot(2);
            $oldSnapshot->setOperation(Snapshot::OPERATION_CHANGE);
            $oldSnapshot->setData(['old' => '1']);
            $oldSnapshot->setTime(date('c', 400));

            $newSnapshot = new Snapshot(3);
            $newSnapshot->setOperation(Snapshot::OPERATION_DELETE);
            $newSnapshot->setData(['new' => '2']);
            $newSnapshot->setTime(date('c', 401));

            $expectedSnapshot = new Snapshot(2);
            $expectedSnapshot->setOperation(Snapshot::OPERATION_DELETE);
            $expectedSnapshot->setData(['new' => '2']);
            $expectedSnapshot->setDataDifference(['+' => ['new' => '2'], '-' => ['oldest' => '0']]);
            $expectedSnapshot->setTime(date('c', 402));

            yield [[$oldSnapshot, $oldestSnapshot], $newSnapshot, $expectedSnapshot, date('c', 402), [3]];

            $newSnapshot = clone($newSnapshot);
            $newSnapshot->setId(null);
            yield [[$oldSnapshot, $oldestSnapshot], $newSnapshot, $expectedSnapshot, date('c', 402), []];
        }
    }

    /**
     * @dataProvider mergingProvider
     */
    public function testMerging($earlierSnapshots, $newSnapshot, $expectedSnapshot, $currentTime, $expectedDeletions = [])
    {
        $this->timeServiceMock->method('getCurrentTime')->willReturn($currentTime);

        $this->snapshotDaoMock
            ->expects($this->once())
            ->method('findEarlierSnapshots')
            ->willReturn($earlierSnapshots);

        $this->snapshotDaoMock
            ->expects($this->exactly($expectedSnapshot === null ? 0 : 1))
            ->method('save')
            ->will($this->returnCallback(function($param) use (&$actualSnapshot)
                {
                    $actualSnapshot = $param;
                }));

        $this->snapshotDaoMock
            ->expects($this->exactly(count($expectedDeletions)))
            ->method('deleteById')
            ->withConsecutive(...array_map(function($del) { return [$del]; }, $expectedDeletions));

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
