<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\SnapshotDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\Tag;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\HistoryService;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractTestCase;
use Szurubooru\Validator;

final class HistoryServiceTest extends AbstractTestCase
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
		$this->validatorMock = $this->mock(Validator::class);
		$this->snapshotDaoMock = $this->mock(SnapshotDao::class);
		$this->globalParamDaoMock = $this->mock(GlobalParamDao::class);
		$this->transactionManagerMock = $this->mock(TransactionManager::class);
		$this->timeServiceMock = $this->mock(TimeService::class);
		$this->authServiceMock = $this->mock(AuthService::class);
	}

	public function testPostChangeSnapshot()
	{
		$tag1 = new Tag();
		$tag2 = new Tag();
		$tag1->setName('tag1');
		$tag2->setName('tag2');
		$post1 = new Post(1);
		$post2 = new Post(2);

		$post = new Post(5);
		$post->setTags([$tag1, $tag2]);
		$post->setRelatedPosts([$post1, $post2]);
		$post->setContentChecksum('checksum');
		$post->setSafety(Post::POST_SAFETY_SKETCHY);
		$post->setSource('amazing source');
		$post->setFlags(Post::FLAG_LOOP);

		$historyService = $this->getHistoryService();
		$snapshot = $historyService->getPostChangeSnapshot($post);

		$this->assertEquals([
			'source' => 'amazing source',
			'safety' => 'sketchy',
			'contentChecksum' => 'checksum',
			'featured' => false,
			'tags' => ['tag1', 'tag2'],
			'relations' => [1, 2],
			'flags' => ['loop'],
		], $snapshot->getData());

		$this->assertEquals(Snapshot::TYPE_POST, $snapshot->getType());
		$this->assertEquals(5, $snapshot->getPrimaryKey());

		return $post;
	}

	/**
	 * @depends testPostChangeSnapshot
	 */
	public function testPostChangeSnapshotFeature($post)
	{
		$param = new GlobalParam;
		$param->setValue($post->getId());
		$this->globalParamDaoMock
			->expects($this->once())
			->method('findByKey')
			->with(GlobalParam::KEY_FEATURED_POST)
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
		return new HistoryService(
			$this->validatorMock,
			$this->snapshotDaoMock,
			$this->globalParamDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock,
			$this->authServiceMock);
	}
}
