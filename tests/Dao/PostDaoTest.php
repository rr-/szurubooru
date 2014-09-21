<?php
namespace Szurubooru\Tests\Dao;

final class PostDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $tagDao;
	private $fileServiceMock;
	private $thumbnailServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->tagDao = new \Szurubooru\Dao\TagDao($this->databaseConnection);
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
	}

	public function testCreating()
	{
		$postDao = $this->getPostDao();

		$post = $this->getPost();
		$savedPost = $postDao->save($post);
		$this->assertEquals('test', $post->getName());
		$this->assertNotNull($savedPost->getId());
	}

	public function testUpdating()
	{
		$postDao = $this->getPostDao();
		$post = $this->getPost();
		$post = $postDao->save($post);
		$this->assertEquals('test', $post->getName());
		$id = $post->getId();
		$post->setName($post->getName() . '2');
		$post = $postDao->save($post);
		$this->assertEquals('test2', $post->getName());
		$this->assertEquals($id, $post->getId());
	}

	public function testGettingAll()
	{
		$postDao = $this->getPostDao();

		$post1 = $this->getPost();
		$post2 = $this->getPost();
		$postDao->save($post1);
		$postDao->save($post2);

		$actual = $postDao->findAll();

		$expected = [
			$post1->getId() => $post1,
			$post2->getId() => $post2,
		];

		$this->assertEntitiesEqual($expected, $actual);
	}

	public function testGettingById()
	{
		$postDao = $this->getPostDao();

		$post1 = $this->getPost();
		$post2 = $this->getPost();
		$postDao->save($post1);
		$postDao->save($post2);

		$actualPost1 = $postDao->findById($post1->getId());
		$actualPost2 = $postDao->findById($post2->getId());
		$this->assertEntitiesEqual($post1, $actualPost1);
		$this->assertEntitiesEqual($post2, $actualPost2);
	}

	public function testDeletingAll()
	{
		$postDao = $this->getPostDao();

		$post1 = $this->getPost();
		$post2 = $this->getPost();
		$postDao->save($post1);
		$postDao->save($post2);

		$postDao->deleteAll();

		$actualPost1 = $postDao->findById($post1->getId());
		$actualPost2 = $postDao->findById($post2->getId());
		$this->assertNull($actualPost1);
		$this->assertNull($actualPost2);
		$this->assertEquals(0, count($postDao->findAll()));
	}

	public function testDeletingById()
	{
		$postDao = $this->getPostDao();

		$post1 = $this->getPost();
		$post2 = $this->getPost();
		$postDao->save($post1);
		$postDao->save($post2);

		$postDao->deleteById($post1->getId());

		$actualPost1 = $postDao->findById($post1->getId());
		$actualPost2 = $postDao->findById($post2->getId());
		$this->assertNull($actualPost1);
		$this->assertEntitiesEqual($actualPost2, $actualPost2);
		$this->assertEquals(1, count($postDao->findAll()));
	}

	public function testSavingTags()
	{
		$tag1 = new \Szurubooru\Entities\Tag();
		$tag1->setName('tag1');
		$tag2 = new \Szurubooru\Entities\Tag();
		$tag2->setName('tag2');
		$testTags = ['tag1' => $tag1, 'tag2' => $tag2];

		$postDao = $this->getPostDao();
		$post = $this->getPost();
		$post->setTags($testTags);
		$postDao->save($post);

		$savedPost = $postDao->findById($post->getId());
		$this->assertEntitiesEqual($testTags, $post->getTags());
		$this->assertEquals(2, count($savedPost->getTags()));

		$this->assertEquals(2, $post->getTagCount());
		$this->assertEquals(2, $savedPost->getTagCount());

		$tagDao = $this->getTagDao();
		$this->assertEquals(2, count($tagDao->findAll()));
	}

	public function testNotLoadingContentForNewPosts()
	{
		$postDao = $this->getPostDao();
		$newlyCreatedPost = $this->getPost();
		$this->assertNull($newlyCreatedPost->getContent());
	}

	public function testLoadingContentPostsForExistingPosts()
	{
		$postDao = $this->getPostDao();
		$post = $this->getPost();
		$postDao->save($post);

		$post = $postDao->findById($post->getId());

		$this->fileServiceMock
			->expects($this->once())
			->method('load')
			->with($post->getContentPath())
			->willReturn('whatever');

		$this->assertEquals('whatever', $post->getContent());
	}

	public function testSavingContent()
	{
		$postDao = $this->getPostDao();
		$post = $this->getPost();
		$post->setContent('whatever');

		$this->thumbnailServiceMock
			->expects($this->exactly(2))
			->method('deleteUsedThumbnails')
			->withConsecutive(
				[$post->getContentPath()],
				[$post->getThumbnailSourceContentPath()]);

		$this->fileServiceMock
			->expects($this->once())
			->method('save')
			->with($post->getContentPath(), 'whatever');

		$postDao->save($post);
	}
	public function testSavingContentAndThumbnail()
	{
		$postDao = $this->getPostDao();
		$post = $this->getPost();
		$post->setContent('whatever');
		$post->setThumbnailSourceContent('an image of sharks');

		$this->thumbnailServiceMock
			->expects($this->exactly(2))
			->method('deleteUsedThumbnails')
			->withConsecutive(
				[$post->getContentPath()],
				[$post->getThumbnailSourceContentPath()]);

		$this->fileServiceMock
			->expects($this->exactly(2))
			->method('save')
			->withConsecutive(
				[$post->getContentPath(), 'whatever'],
				[$post->getThumbnailSourceContentPath(), 'an image of sharks']);

		$postDao->save($post);
	}


	private function getPostDao()
	{
		return new \Szurubooru\Dao\PostDao(
			$this->databaseConnection,
			$this->tagDao,
			$this->fileServiceMock,
			$this->thumbnailServiceMock);
	}

	private function getTagDao()
	{
		return $this->tagDao;
	}

	private function getPost()
	{
		$post = new \Szurubooru\Entities\Post();
		$post->setName('test');
		$post->setUploadTime('whatever');
		$post->setSafety(\Szurubooru\Entities\Post::POST_SAFETY_SAFE);
		$post->setContentType(\Szurubooru\Entities\Post::POST_TYPE_YOUTUBE);
		$post->setContentChecksum('whatever');
		return $post;
	}
}
