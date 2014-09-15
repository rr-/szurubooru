<?php
namespace Szurubooru\Tests\Dao;

final class PostDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
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
		foreach ($actual as $post)
			$post->resetLazyLoaders();

		$expected = [
			$post1->getId() => $post1,
			$post2->getId() => $post2,
		];

		$this->assertEquals($expected, $actual);
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
		$actualPost1->resetLazyLoaders();
		$actualPost2->resetLazyLoaders();
		$this->assertEquals($post1, $actualPost1);
		$this->assertEquals($post2, $actualPost2);
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
		$this->assertEquals(null, $actualPost1);
		$this->assertEquals(null, $actualPost2);
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
		$this->assertEquals(null, $actualPost1);
		$this->assertEquals($actualPost2, $actualPost2);
		$this->assertEquals(1, count($postDao->findAll()));
	}

	public function testSavingTags()
	{
		$testTags = ['tag1', 'tag2'];
		$postDao = $this->getPostDao();
		$post = $this->getPost();
		$post->setTags($testTags);
		$postDao->save($post);

		$savedPost = $postDao->findById($post->getId());
		$this->assertEquals($testTags, $post->getTags());
		$this->assertEquals($post->getTags(), $savedPost->getTags());

		$tagDao = $this->getTagDao();
		$this->assertEquals(2, count($tagDao->findAll()));
	}

	private function getPostDao()
	{
		return new \Szurubooru\Dao\PostDao($this->databaseConnection);
	}

	private function getTagDao()
	{
		return new \Szurubooru\Dao\TagDao($this->databaseConnection);
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
