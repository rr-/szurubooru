<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\FavoritesDao;
use Szurubooru\Entities\Favorite;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class FavoritesDaoTest extends AbstractDatabaseTestCase
{
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->timeServiceMock = $this->mock(TimeService::class);
	}

	public function testSaving()
	{
		$user = new User(1);
		$user->setName('olivia');

		$post = new Post(2);
		$post->setName('sword');

		$favorite = new Favorite();
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
		return new FavoritesDao(
			$this->databaseConnection,
			$this->timeServiceMock);
	}
}
