<?php
namespace Szurubooru\Tests\Dao;

class PostScoreDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
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

		$postScore = new \Szurubooru\Entities\PostScore();
		$postScore->setUser($user);
		$postScore->setPost($post);
		$postScore->setTime(date('c'));
		$postScore->setScore(1);
		$postScoreDao = $this->getPostScoreDao();
		$postScoreDao->save($postScore);

		$this->userDaoMock->expects($this->once())->method('findById')->with(1)->willReturn($user);
		$this->postDaoMock->expects($this->once())->method('findById')->with(2)->willReturn($post);

		$savedPostScore = $postScoreDao->findById($postScore->getId());
		$this->assertEquals(1, $savedPostScore->getUserId());
		$this->assertEquals(2, $savedPostScore->getPostId());
		$this->assertEquals($postScore->getTime(), $savedPostScore->getTime());
		$this->assertEntitiesEqual($user, $savedPostScore->getUser());
		$this->assertEntitiesEqual($post, $savedPostScore->getPost());
	}

	public function testFindingByUserAndPost()
	{
		$post1 = new \Szurubooru\Entities\Post(1);
		$post2 = new \Szurubooru\Entities\Post(2);
		$user1 = new \Szurubooru\Entities\User(3);
		$user2 = new \Szurubooru\Entities\User(4);

		$postScore1 = new \Szurubooru\Entities\PostScore();
		$postScore1->setUser($user1);
		$postScore1->setPost($post1);
		$postScore1->setTime(date('c', mktime(1)));
		$postScore1->setScore(1);

		$postScore2 = new \Szurubooru\Entities\PostScore();
		$postScore2->setUser($user2);
		$postScore2->setPost($post2);
		$postScore2->setTime(date('c', mktime(2)));
		$postScore2->setScore(0);

		$postScore3 = new \Szurubooru\Entities\PostScore();
		$postScore3->setUser($user1);
		$postScore3->setPost($post2);
		$postScore3->setTime(date('c', mktime(3)));
		$postScore3->setScore(-1);

		$postScoreDao = $this->getPostScoreDao();
		$postScoreDao->save($postScore1);
		$postScoreDao->save($postScore2);
		$postScoreDao->save($postScore3);

		$this->assertEntitiesEqual($postScore1, $postScoreDao->getScore($user1, $post1));
		$this->assertEntitiesEqual($postScore2, $postScoreDao->getScore($user2, $post2));
		$this->assertEntitiesEqual($postScore3, $postScoreDao->getScore($user1, $post2));
		$this->assertNull($postScoreDao->getScore($user2, $post1));
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findOneBy('postId', $post->getId());
	}

	private function getPostScoreDao()
	{
		return new \Szurubooru\Dao\PostScoreDao(
			$this->databaseConnection,
			$this->userDaoMock,
			$this->postDaoMock,
			$this->timeServiceMock);
	}
}
