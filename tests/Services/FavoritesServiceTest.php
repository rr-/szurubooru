<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\FavoritesDao;
use Szurubooru\Dao\ScoreDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Favorite;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Services\FavoritesService;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractTestCase;

final class FavoritesServiceTest extends AbstractTestCase
{
	private $favoritesDaoMock;
	private $scoreDaoMock;
	private $userDaoMock;
	private $transactionManagerMock;
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->favoritesDaoMock = $this->mock(FavoritesDao::class);
		$this->scoreDaoMock = $this->mock(ScoreDao::class);
		$this->userDaoMock = $this->mock(UserDao::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->timeServiceMock = $this->mock(TimeService::class);
	}

	public function testAdding()
	{
		$user = new User(1);
		$post = new Post(2);
		$fav = new Favorite();
		$fav->setUserId($user->getId());
		$fav->setPostId($post->getId());
		$this->favoritesDaoMock->expects($this->once())->method('set')->with($user, $post);

		$favoritesService = $this->getFavoritesService();
		$favoritesService->addFavorite($user, $post);
	}

	public function testDeleting()
	{
		$user = new User();
		$post = new Post();
		$fav = new Favorite(3);
		$this->favoritesDaoMock->expects($this->once())->method('delete')->with($user, $post);

		$favoritesService = $this->getFavoritesService();
		$favoritesService->deleteFavorite($user, $post);
	}

	public function testGettingByPost()
	{
		$post = new Post();
		$fav1 = new Favorite();
		$fav2 = new Favorite();
		$fav1->setUser(new User(1));
		$fav2->setUser(new User(2));

		$this->favoritesDaoMock->expects($this->once())->method('findByEntity')->with($post)->willReturn([$fav1, $fav2]);
		$this->userDaoMock->expects($this->once())->method('findByIds')->with([1, 2]);

		$favoritesService = $this->getFavoritesService();
		$favoritesService->getFavoriteUsers($post);
	}

	private function getFavoritesService()
	{
		return new FavoritesService(
			$this->favoritesDaoMock,
			$this->scoreDaoMock,
			$this->userDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock);
	}
}
