<?php
namespace Szurubooru\Tests\Services;

final class FavoritesServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $favoritesDaoMock;
	private $scoreDaoMock;
	private $userDaoMock;
	private $transactionManagerMock;
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->favoritesDaoMock = $this->mock(\Szurubooru\Dao\FavoritesDao::class);
		$this->scoreDaoMock = $this->mock(\Szurubooru\Dao\ScoreDao::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
	}

	public function testAdding()
	{
		$user = new \Szurubooru\Entities\User(1);
		$post = new \Szurubooru\Entities\Post(2);
		$fav = new \Szurubooru\Entities\Favorite();
		$fav->setUserId($user->getId());
		$fav->setPostId($post->getId());
		$this->favoritesDaoMock->expects($this->once())->method('set')->with($user, $post);

		$favoritesService = $this->getFavoritesService();
		$favoritesService->addFavorite($user, $post);
	}

	public function testDeleting()
	{
		$user = new \Szurubooru\Entities\User();
		$post = new \Szurubooru\Entities\Post();
		$fav = new \Szurubooru\Entities\Favorite(3);
		$this->favoritesDaoMock->expects($this->once())->method('delete')->with($user, $post);

		$favoritesService = $this->getFavoritesService();
		$favoritesService->deleteFavorite($user, $post);
	}

	public function testGettingByPost()
	{
		$post = new \Szurubooru\Entities\Post();
		$fav1 = new \Szurubooru\Entities\Favorite();
		$fav2 = new \Szurubooru\Entities\Favorite();
		$fav1->setUser(new \Szurubooru\Entities\User(1));
		$fav2->setUser(new \Szurubooru\Entities\User(2));

		$this->favoritesDaoMock->expects($this->once())->method('findByEntity')->with($post)->willReturn([$fav1, $fav2]);
		$this->userDaoMock->expects($this->once())->method('findByIds')->with([1, 2]);

		$favoritesService = $this->getFavoritesService();
		$favoritesService->getFavoriteUsers($post);
	}

	private function getFavoritesService()
	{
		return new \Szurubooru\Services\FavoritesService(
			$this->favoritesDaoMock,
			$this->scoreDaoMock,
			$this->userDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock);
	}
}
