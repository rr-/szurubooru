<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\ScoreDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Score;
use Szurubooru\Entities\User;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class ScoreDaoTest extends AbstractDatabaseTestCase
{
    public function testSaving()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);
        $scoreDao = Injector::get(ScoreDao::class);

        $user = self::getTestUser('olivia');
        $userDao->save($user);

        $post = self::getTestPost();
        $postDao->save($post);

        $score = new Score();
        $score->setUserId($user->getId());
        $score->setPostId($post->getId());
        $score->setTime(date('c'));
        $score->setScore(1);
        $scoreDao->save($score);

        $savedScore = $scoreDao->findById($score->getId());
        $this->assertNotNull($savedScore->getUserId());
        $this->assertNotNull($savedScore->getPostId());
        $this->assertEquals($score->getTime(), $savedScore->getTime());
        $this->assertEquals($user->getId(), $savedScore->getUserId());
        $this->assertEquals($post->getId(), $savedScore->getPostId());
    }

    public function testFindingByUserAndPost()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);
        $scoreDao = Injector::get(ScoreDao::class);

        $user1 = self::getTestUser('olivia');
        $user2 = self::getTestUser('victoria');
        $userDao->save($user1);
        $userDao->save($user2);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $postDao->save($post1);
        $postDao->save($post2);

        $score1 = new Score();
        $score1->setUserId($user1->getId());
        $score1->setPostId($post1->geTId());
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

        $scoreDao->save($score1);
        $scoreDao->save($score2);
        $scoreDao->save($score3);

        $this->assertEntitiesEqual($score1, $scoreDao->getUserScore($user1, $post1));
        $this->assertEntitiesEqual($score2, $scoreDao->getUserScore($user2, $post2));
        $this->assertEntitiesEqual($score3, $scoreDao->getUserScore($user1, $post2));
        $this->assertNull($scoreDao->getUserScore($user2, $post1));
    }
}
