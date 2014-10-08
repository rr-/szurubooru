<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\ScoreDao;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Score;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class ScoreDaoTest extends AbstractDatabaseTestCase
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

		$score = new Score();
		$score->setUserId($user->getId());
		$score->setPostId($post->getId());
		$score->setTime(date('c'));
		$score->setScore(1);
		$scoreDao = $this->getScoreDao();
		$scoreDao->save($score);

		$savedScore = $scoreDao->findById($score->getId());
		$this->assertEquals(1, $savedScore->getUserId());
		$this->assertEquals(2, $savedScore->getPostId());
		$this->assertEquals($score->getTime(), $savedScore->getTime());
		$this->assertEquals($user->getId(), $savedScore->getUserId());
		$this->assertEquals($post->getId(), $savedScore->getPostId());
	}

	public function testFindingByUserAndPost()
	{
		$post1 = new Post(1);
		$post2 = new Post(2);
		$user1 = new User(3);
		$user2 = new User(4);

		$score1 = new Score();
		$score1->setUserId($user1->getId());
		$score1->setPostId($post1->getId());
		$score1->setTime(date('c', mktime(1)));
		$score1->setScore(1);

		$score2 = new Score();
		$score2->setUserId($user2->getId());
		$score2->setPostId($post2->getId());
		$score2->setTime(date('c', mktime(2)));
		$score2->setScore(0);

		$score3 = new Score();
		$score3->setUserId($user1->getId());
		$score3->setPostId($post2->getId());
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

	public function findByPost(Post $post)
	{
		return $this->findOneBy('postId', $post->getId());
	}

	private function getScoreDao()
	{
		return new ScoreDao(
			$this->databaseConnection,
			$this->timeServiceMock);
	}
}
