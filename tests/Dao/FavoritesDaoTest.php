<?php
namespace Szurubooru\Tests\Dao;

class FavoritesDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
	}

	public function testSaving()
	{
		$user = new \Szurubooru\Entities\User(1);
		$user->setName('olivia');

		$post = new \Szurubooru\Entities\Post(2);
		$post->setName('sword');

		$favorite = new \Szurubooru\Entities\Favorite();
		$favorite->setUserId($user->getId());
		$favorite->setPostId($post->getId());
		$favorite->setTime(date('c'));
		$favoritesDao = $this->getFavoritesDao();
		$favoritesDao->save($favorite);

		$savedFavorite = $favoritesDao->findById($favorite->getId());
		$this->assertEquals(1, $savedFavorite->getUserId());
		$this->assertEquals(2, $savedFavorite->getPostId());
		$this->assertEquals($favorite->getTime(), $savedFavorite->getTime());
		$this->assertEquals($user->getId(), $savedFavorite->getUserId());
		$this->assertEquals($post->getId(), $savedFavorite->getPostId());
	}

	private function getFavoritesDao()
	{
		return new \Szurubooru\Dao\FavoritesDao(
			$this->databaseConnection,
			$this->timeServiceMock);
	}
}
