<?php
namespace Szurubooru\Tests\Services;

final class ScoreServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $scoreDaoMock;
	private $favoritesDaoMock;
	private $userDaoMock;
	private $transactionManagerMock;
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->scoreDaoMock = $this->mock(\Szurubooru\Dao\ScoreDao::class);
		$this->favoritesDaoMock = $this->mock(\Szurubooru\Dao\FavoritesDao::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
	}

	public function testSetting()
	{
		$user = new \Szurubooru\Entities\User(1);
		$post = new \Szurubooru\Entities\Post(2);
		$score = new \Szurubooru\Entities\Score();
		$score->setUserId($user->getId());
		$score->setPostId($post->getId());
		$score->setScore(1);
		$this->scoreDaoMock->expects($this->once())->method('setScore')->with($user, $post)->willReturn(null);

		$scoreService = $this->getScoreService();
		$scoreService->setScore($user, $post, 1);
	}

	public function testSettingInvalid()
	{
		$user = new \Szurubooru\Entities\User(1);
		$post = new \Szurubooru\Entities\Post(2);
		$this->setExpectedException(\Exception::class);
		$scoreService = $this->getScoreService();
		$scoreService->setScore($user, $post, 2);
	}

	public function testGetting()
	{
		$user = new \Szurubooru\Entities\User();
		$post = new \Szurubooru\Entities\Post();
		$score = new \Szurubooru\Entities\Score(3);
		$this->scoreDaoMock->expects($this->once())->method('getScore')->with($user, $post)->willReturn($score);

		$scoreService = $this->getScoreService();
		$retrievedScore = $scoreService->getScore($user, $post);
		$this->assertEquals($score, $retrievedScore);
	}

	private function getScoreService()
	{
		return new \Szurubooru\Services\ScoreService(
			$this->scoreDaoMock,
			$this->favoritesDaoMock,
			$this->userDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock);
	}
}
