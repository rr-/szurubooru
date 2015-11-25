<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\CommentDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Comment;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class CommentDaoTest extends AbstractDatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testSaving()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);
        $commentDao = Injector::get(CommentDao::class);

        $user = self::getTestUser('olivia');
        $userDao->save($user);

        $post = self::getTestPost();
        $postDao->save($post);

        $comment = new Comment();
        $comment->setUser($user);
        $comment->setPost($post);
        $comment->setCreationTime(date('c'));
        $comment->setLastEditTime(date('c'));
        $comment->setText('whatever');
        $commentDao->save($comment);

        $savedComment = $commentDao->findById($comment->getId());
        $this->assertNotNull($savedComment->getUserId());
        $this->assertNotNull($savedComment->getPostId());
        $this->assertEquals($comment->getCreationTime(), $savedComment->getCreationTime());
        $this->assertEquals($comment->getLastEditTime(), $savedComment->getLastEditTime());
        $this->assertEquals($comment->getText(), $savedComment->getText());
        $this->assertEntitiesEqual($user, $savedComment->getUser());
        $this->assertEntitiesEqual($post, $savedComment->getPost());
    }

    public function testPostMetadataSyncInsert()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);
        $commentDao = Injector::get(CommentDao::class);

        $user = self::getTestUser('olivia');
        $userDao->save($user);

        $post = self::getTestPost();
        $postDao->save($post);

        $this->assertEquals(0, $post->getCommentCount());
        $this->assertNotNull($post->getId());

        $comment = new Comment();
        $comment->setUser($user);
        $comment->setPost($post);
        $comment->setCreationTime(date('c'));
        $comment->setLastEditTime(date('c'));
        $comment->setText('whatever');
        $commentDao->save($comment);

        $post = $postDao->findById($post->getId());
        $this->assertNotNull($post);
        $this->assertEquals(1, $post->getCommentCount());
    }

    public function testPostMetadataSyncDelete()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);
        $commentDao = Injector::get(CommentDao::class);

        $user = self::getTestUser('olivia');
        $userDao->save($user);

        $post = self::getTestPost();
        $postDao->save($post);

        $this->assertEquals(0, $post->getCommentCount());
        $this->assertNotNull($post->getId());

        $comment = new Comment();
        $comment->setUser($user);
        $comment->setPost($post);
        $comment->setCreationTime(date('c'));
        $comment->setLastEditTime(date('c'));
        $comment->setText('whatever');
        $commentDao->save($comment);

        $commentDao->deleteById($comment->getId());

        $this->assertNotNull($post->getId());
        $post = $postDao->findById($post->getId());
        $this->assertNotNull($post);
        $this->assertEquals(0, $post->getCommentCount());
    }
}
