<?php
namespace Szurubooru\Tests\Dao;

final class PostDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testCreating()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post = new \Szurubooru\Entities\Post();
		$post->setName('test');
		$savedPost = $postDao->save($post);
		$this->assertEquals('test', $post->getName());
		$this->assertNotNull($savedPost->getId());
	}

	public function testUpdating()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);
		$post = new \Szurubooru\Entities\Post();
		$post->setName('test');
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
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->setName('test2');
		$post2 = new \Szurubooru\Entities\Post();
		$post2->setName('test2');

		$postDao->save($post1);
		$postDao->save($post2);

		$actual = $postDao->findAll();
		$expected = [
			$post1->getId() => $post1,
			$post2->getId() => $post2,
		];

		$this->assertEquals($expected, $actual);
	}

	public function testGettingById()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->setName('test2');
		$post2 = new \Szurubooru\Entities\Post();
		$post2->setName('test2');

		$postDao->save($post1);
		$postDao->save($post2);

		$actualPost1 = $postDao->findById($post1->getId());
		$actualPost2 = $postDao->findById($post2->getId());
		$this->assertEquals($post1, $actualPost1);
		$this->assertEquals($post2, $actualPost2);
	}

	public function testDeletingAll()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->setName('test2');
		$post2 = new \Szurubooru\Entities\Post();
		$post2->setName('test2');

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
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->setName('test2');
		$post2 = new \Szurubooru\Entities\Post();
		$post2->setName('test2');

		$postDao->save($post1);
		$postDao->save($post2);

		$postDao->deleteById($post1->getId());

		$actualPost1 = $postDao->findById($post1->getId());
		$actualPost2 = $postDao->findById($post2->getId());
		$this->assertEquals(null, $actualPost1);
		$this->assertEquals($actualPost2, $actualPost2);
		$this->assertEquals(1, count($postDao->findAll()));
	}
}
