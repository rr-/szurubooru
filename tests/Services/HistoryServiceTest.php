<?php
namespace Szurubooru\Tests\Services;

class HistoryServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $validatorMock;
	private $snapshotDaoMock;
	private $globalParamDaoMock;
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
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->snapshotDaoMock = $this->mock(\Szurubooru\Dao\SnapshotDao::class);
		$this->globalParamDaoMock = $this->mock(\Szurubooru\Dao\GlobalParamDao::class);
		$this->transactionManagerMock = $this->mock(\Szurubooru\Dao\TransactionManager::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->authServiceMock = $this->mock(\Szurubooru\Services\AuthService::class);
	}

	public function testPostChangeSnapshot()
	{
		$tag1 = new \Szurubooru\Entities\Tag();
		$tag2 = new \Szurubooru\Entities\Tag();
		$tag1->setName('tag1');
		$tag2->setName('tag2');
		$post1 = new \Szurubooru\Entities\Post(1);
		$post2 = new \Szurubooru\Entities\Post(2);

		$post = new \Szurubooru\Entities\Post(5);
		$post->setTags([$tag1, $tag2]);
		$post->setRelatedPosts([$post1, $post2]);
		$post->setContentChecksum('checksum');
		$post->setSafety(\Szurubooru\Entities\Post::POST_SAFETY_SKETCHY);
		$post->setSource('amazing source');

		$historyService = $this->getHistoryService();
		$snapshot = $historyService->getPostChangeSnapshot($post);

		$this->assertEquals([
			'source' => 'amazing source',
			'safety' => 'sketchy',
			'contentChecksum' => 'checksum',
			'featured' => false,
			'tags' => ['tag1', 'tag2'],
			'relations' => [1, 2]
		], $snapshot->getData());

		$this->assertEquals(\Szurubooru\Entities\Snapshot::TYPE_POST, $snapshot->getType());
		$this->assertEquals(5, $snapshot->getPrimaryKey());

		return $post;
	}

	/**
	 * @depends testPostChangeSnapshot
	 */
	public function testPostChangeSnapshotFeature($post)
	{
		$param = new \Szurubooru\Entities\GlobalParam;
		$param->setValue($post->getId());
		$this->globalParamDaoMock
			->expects($this->once())
			->method('findByKey')
			->with(\Szurubooru\Entities\GlobalParam::KEY_FEATURED_POST)
			->willReturn($param);

		$historyService = $this->getHistoryService();
		$snapshot = $historyService->getPostChangeSnapshot($post);

		$this->assertTrue($snapshot->getData()['featured']);
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
		return new \Szurubooru\Services\HistoryService(
			$this->validatorMock,
			$this->snapshotDaoMock,
			$this->globalParamDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock,
			$this->authServiceMock);
	}
}
