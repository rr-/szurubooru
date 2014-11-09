<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\PostNoteDao;
use Szurubooru\Entities\GlobalParam;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\PostNote;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\Tag;
use Szurubooru\Services\PostSnapshotProvider;
use Szurubooru\Tests\AbstractTestCase;

class PostSnapshotProviderTest extends AbstractTestCase
{
	private $globalParamDaoMock;

	public function setUp()
	{
		parent::setUp();
		$this->globalParamDaoMock = $this->mock(GlobalParamDao::class);
		$this->postNoteDaoMock = $this->mock(PostNoteDao::class);
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

		$this->postNoteDaoMock
			->method('findByPostId')
			->with($post->getId())
			->willReturn([$this->getTestPostNote()]);

		$postSnapshotProvider = $this->getPostSnapshotProvider();
		$snapshot = $postSnapshotProvider->getChangeSnapshot($post);

		$this->assertEquals([
			'source' => 'amazing source',
			'safety' => 'sketchy',
			'contentChecksum' => 'checksum',
			'featured' => false,
			'tags' => ['tag1', 'tag2'],
			'relations' => [1, 2],
			'flags' => ['loop'],
			'notes' => [['x' => 5, 'y' => 6, 'w' => 7, 'h' => 8, 'text' => 'text']],
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

		$this->postNoteDaoMock
			->method('findByPostId')
			->with($post->getId())
			->willReturn([]);

		$postSnapshotProvider = $this->getPostSnapshotProvider();
		$snapshot = $postSnapshotProvider->getChangeSnapshot($post);

		$this->assertTrue($snapshot->getData()['featured']);
	}

	private function getPostSnapshotProvider()
	{
		return new PostSnapshotProvider($this->globalParamDaoMock, $this->postNoteDaoMock);
	}

	private function getTestPostNote()
	{
		$postNote = new PostNote();
		$postNote->setLeft(5);
		$postNote->setTop(6);
		$postNote->setWidth(7);
		$postNote->setHeight(8);
		$postNote->setText('text');
		return $postNote;
	}
}
