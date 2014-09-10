<?php
namespace Szurubooru\Tests\Dao;

final class PostDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function testCreating()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post = new \Szurubooru\Entities\Post();
		$post->name = 'test';
		$savedPost = $postDao->save($post);
		$this->assertEquals('test', $post->name);
		$this->assertNotNull($savedPost->id);
	}

	public function testUpdating()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);
		$post = new \Szurubooru\Entities\Post();
		$post->name = 'test';
		$post = $postDao->save($post);
		$id = $post->id;
		$post->name .= '2';
		$post = $postDao->save($post);
		$this->assertEquals('test2', $post->name);
		$this->assertEquals($id, $post->id);
	}

	public function testGettingAll()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->name = 'test2';
		$post2 = new \Szurubooru\Entities\Post();
		$post2->name = 'test2';

		$postDao->save($post1);
		$postDao->save($post2);

		$actual = $postDao->getAll();
		$expected = [
			$post1->id => $post1,
			$post2->id => $post2,
		];

		$this->assertEquals($expected, $actual);
	}

	public function testGettingById()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->name = 'test2';
		$post2 = new \Szurubooru\Entities\Post();
		$post2->name = 'test2';

		$postDao->save($post1);
		$postDao->save($post2);

		$actualPost1 = $postDao->getById($post1->id);
		$actualPost2 = $postDao->getById($post2->id);
		$this->assertEquals($post1, $actualPost1);
		$this->assertEquals($post2, $actualPost2);
	}

	public function testDeletingAll()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->name = 'test2';
		$post2 = new \Szurubooru\Entities\Post();
		$post2->name = 'test2';

		$postDao->save($post1);
		$postDao->save($post2);

		$postDao->deleteAll();

		$actualPost1 = $postDao->getById($post1->id);
		$actualPost2 = $postDao->getById($post2->id);
		$this->assertEquals(null, $actualPost1);
		$this->assertEquals(null, $actualPost2);
		$this->assertEquals(0, count($postDao->getAll()));
	}

	public function testDeletingById()
	{
		$postDao = new \Szurubooru\Dao\PostDao($this->databaseConnection);

		$post1 = new \Szurubooru\Entities\Post();
		$post1->name = 'test2';
		$post2 = new \Szurubooru\Entities\Post();
		$post2->name = 'test2';

		$postDao->save($post1);
		$postDao->save($post2);

		$postDao->deleteById($post1->id);

		$actualPost1 = $postDao->getById($post1->id);
		$actualPost2 = $postDao->getById($post2->id);
		$this->assertEquals(null, $actualPost1);
		$this->assertEquals($actualPost2, $actualPost2);
		$this->assertEquals(1, count($postDao->getAll()));
	}
}
