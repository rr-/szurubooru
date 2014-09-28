<?php
namespace Szurubooru\Tests\Dao;

class FavoritesDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $userDaoMock;
	private $postDaoMock;
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->postDaoMock = $this->mock(\Szurubooru\Dao\PostDao::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
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

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findOneBy('postId', $post->getId());
	}

	private function getFavoritesDao()
	{
		return new \Szurubooru\Dao\FavoritesDao(
			$this->databaseConnection,
			$this->userDaoMock,
			$this->postDaoMock,
			$this->timeServiceMock);
	}
}
