<?php
namespace Szurubooru\Tests\Dao;

class FavoritesDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $userDaoMock;
	private $postDaoMock;

	public function setUp()
	{
		parent::setUp();
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->postDaoMock = $this->mock(\Szurubooru\Dao\PostDao::class);
	}

	public function testSaving()
	{
		$user = new \Szurubooru\Entities\User(1);
		$user->setName('olivia');

		$post = new \Szurubooru\Entities\Post(2);
		$post->setName('sword');

		$favorite = new \Szurubooru\Entities\Favorite();
		$favorite->setUser($user);
		$favorite->setPost($post);
		$favorite->setTime('whatever');
		$favoritesDao = $this->getFavoritesDao();
		$favoritesDao->save($favorite);

		$this->userDaoMock->expects($this->once())->method('findById')->with(1)->willReturn($user);
		$this->postDaoMock->expects($this->once())->method('findById')->with(2)->willReturn($post);

		$savedFavorite = $favoritesDao->findById($favorite->getId());
		$this->assertEquals(1, $savedFavorite->getUserId());
		$this->assertEquals(2, $savedFavorite->getPostId());
		$this->assertEquals('whatever', $savedFavorite->getTime());
		$this->assertEntitiesEqual($user, $savedFavorite->getUser());
		$this->assertEntitiesEqual($post, $savedFavorite->getPost());
	}

	public function testFindingByUserAndPost()
	{
		$post1 = new \Szurubooru\Entities\Post(1);
		$post2 = new \Szurubooru\Entities\Post(2);
		$user1 = new \Szurubooru\Entities\User(3);
		$user2 = new \Szurubooru\Entities\User(4);

		$fav1 = new \Szurubooru\Entities\Favorite();
		$fav1->setUser($user1);
		$fav1->setPost($post1);
		$fav1->setTime('time1');

		$fav2 = new \Szurubooru\Entities\Favorite();
		$fav2->setUser($user2);
		$fav2->setPost($post2);
		$fav2->setTime('time2');

		$fav3 = new \Szurubooru\Entities\Favorite();
		$fav3->setUser($user1);
		$fav3->setPost($post2);
		$fav3->setTime('time3');

		$favoritesDao = $this->getFavoritesDao();
		$favoritesDao->save($fav1);
		$favoritesDao->save($fav2);
		$favoritesDao->save($fav3);

		$this->assertEntitiesEqual($fav1, $favoritesDao->findByUserAndPost($user1, $post1));
		$this->assertEntitiesEqual($fav2, $favoritesDao->findByUserAndPost($user2, $post2));
		$this->assertEntitiesEqual($fav3, $favoritesDao->findByUserAndPost($user1, $post2));
		$this->assertNull($favoritesDao->findByUserAndPost($user2, $post1));
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findOneBy('postId', $post->getId());
	}

	private function getFavoritesDao()
	{
		return new \Szurubooru\Dao\FavoritesDao(
			$this->databaseConnection,
			$this->userDaoMock,
			$this->postDaoMock);
	}
}
