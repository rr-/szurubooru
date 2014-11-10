<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\FavoritesDao;
use Szurubooru\Dao\ScoreDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Score;
use Szurubooru\Entities\User;
use Szurubooru\Services\ScoreService;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractTestCase;

final class ScoreServiceTest extends AbstractTestCase
{
	private $scoreDaoMock;
	private $favoritesDaoMock;
	private $userDaoMock;
	private $transactionManagerMock;
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->scoreDaoMock = $this->mock(ScoreDao::class);
		$this->favoritesDaoMock = $this->mock(FavoritesDao::class);
		$this->userDaoMock = $this->mock(UserDao::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->timeServiceMock = $this->mock(TimeService::class);
	}

	public function testSetting()
	{
		$user = new User(1);
		$post = new Post(2);
		$score = new Score();
		$score->setUserId($user->getId());
		$score->setPostId($post->getId());
		$score->setScore(1);
		$this->scoreDaoMock->expects($this->once())->method('setUserScore')->with($user, $post)->willReturn(null);

		$scoreService = $this->getScoreService();
		$scoreService->setUserScore($user, $post, 1);
	}

	public function testSettingInvalid()
	{
		$user = new User(1);
		$post = new Post(2);
		$this->setExpectedException(\Exception::class);
		$scoreService = $this->getScoreService();
		$scoreService->setUserScore($user, $post, 2);
	}

	public function testGetting()
	{
		$user = new User();
		$post = new Post();
		$score = new Score(3);
		$this->scoreDaoMock->expects($this->once())->method('getUserScore')->with($user, $post)->willReturn($score);

		$scoreService = $this->getScoreService();
		$retrievedScore = $scoreService->getUserScore($user, $post);
		$this->assertEquals($score, $retrievedScore);
	}

	private function getScoreService()
	{
		return new ScoreService(
			$this->scoreDaoMock,
			$this->favoritesDaoMock,
			$this->userDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock);
	}
}
