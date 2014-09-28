<?php
namespace Szurubooru\Tests\Services;

final class PostScoreServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $postScoreDaoMock;
	private $favoritesDaoMock;
	private $userDaoMock;
	private $transactionManagerMock;
	private $timeServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->postScoreDaoMock = $this->mock(\Szurubooru\Dao\PostScoreDao::class);
		$this->favoritesDaoMock = $this->mock(\Szurubooru\Dao\FavoritesDao::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
	}

	public function testSetting()
	{
		$user = new \Szurubooru\Entities\User(1);
		$post = new \Szurubooru\Entities\Post(2);
		$postScore = new \Szurubooru\Entities\PostScore();
		$postScore->setUserId($user->getId());
		$postScore->setPostId($post->getId());
		$postScore->setScore(1);
		$this->postScoreDaoMock->expects($this->once())->method('setScore')->with($user, $post)->willReturn(null);

		$postScoreService = $this->getPostScoreService();
		$postScoreService->setScore($user, $post, 1);
	}

	public function testSettingInvalid()
	{
		$user = new \Szurubooru\Entities\User(1);
		$post = new \Szurubooru\Entities\Post(2);
		$this->setExpectedException(\Exception::class);
		$postScoreService = $this->getPostScoreService();
		$postScoreService->setScore($user, $post, 2);
	}

	public function testGetting()
	{
		$user = new \Szurubooru\Entities\User();
		$post = new \Szurubooru\Entities\Post();
		$postScore = new \Szurubooru\Entities\PostScore(3);
		$this->postScoreDaoMock->expects($this->once())->method('getScore')->with($user, $post)->willReturn($postScore);

		$postScoreService = $this->getPostScoreService();
		$retrievedScore = $postScoreService->getScore($user, $post);
		$this->assertEquals($postScore, $retrievedScore);
	}

	private function getPostScoreService()
	{
		return new \Szurubooru\Services\PostScoreService(
			$this->postScoreDaoMock,
			$this->favoritesDaoMock,
			$this->userDaoMock,
			$this->transactionManagerMock,
			$this->timeServiceMock);
	}
}
