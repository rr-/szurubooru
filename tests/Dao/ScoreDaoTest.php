<?php
namespace Szurubooru\Tests\Dao;

class ScoreDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
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

		$score = new \Szurubooru\Entities\Score();
		$score->setUser($user);
		$score->setPost($post);
		$score->setTime(date('c'));
		$score->setScore(1);
		$scoreDao = $this->getScoreDao();
		$scoreDao->save($score);

		$this->userDaoMock->expects($this->once())->method('findById')->with(1)->willReturn($user);
		$this->postDaoMock->expects($this->once())->method('findById')->with(2)->willReturn($post);

		$savedScore = $scoreDao->findById($score->getId());
		$this->assertEquals(1, $savedScore->getUserId());
		$this->assertEquals(2, $savedScore->getPostId());
		$this->assertEquals($score->getTime(), $savedScore->getTime());
		$this->assertEntitiesEqual($user, $savedScore->getUser());
		$this->assertEntitiesEqual($post, $savedScore->getPost());
	}

	public function testFindingByUserAndPost()
	{
		$post1 = new \Szurubooru\Entities\Post(1);
		$post2 = new \Szurubooru\Entities\Post(2);
		$user1 = new \Szurubooru\Entities\User(3);
		$user2 = new \Szurubooru\Entities\User(4);

		$score1 = new \Szurubooru\Entities\Score();
		$score1->setUser($user1);
		$score1->setPost($post1);
		$score1->setTime(date('c', mktime(1)));
		$score1->setScore(1);

		$score2 = new \Szurubooru\Entities\Score();
		$score2->setUser($user2);
		$score2->setPost($post2);
		$score2->setTime(date('c', mktime(2)));
		$score2->setScore(0);

		$score3 = new \Szurubooru\Entities\Score();
		$score3->setUser($user1);
		$score3->setPost($post2);
		$score3->setTime(date('c', mktime(3)));
		$score3->setScore(-1);

		$scoreDao = $this->getScoreDao();
		$scoreDao->save($score1);
		$scoreDao->save($score2);
		$scoreDao->save($score3);

		$this->assertEntitiesEqual($score1, $scoreDao->getScore($user1, $post1));
		$this->assertEntitiesEqual($score2, $scoreDao->getScore($user2, $post2));
		$this->assertEntitiesEqual($score3, $scoreDao->getScore($user1, $post2));
		$this->assertNull($scoreDao->getScore($user2, $post1));
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findOneBy('postId', $post->getId());
	}

	private function getScoreDao()
	{
		return new \Szurubooru\Dao\ScoreDao(
			$this->databaseConnection,
			$this->userDaoMock,
			$this->postDaoMock,
			$this->timeServiceMock);
	}
}
