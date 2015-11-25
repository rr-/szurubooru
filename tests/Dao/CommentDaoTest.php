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
    private $userDaoMock;
    private $postDaoMock;

    public function setUp()
    {
        parent::setUp();
        $this->userDaoMock = $this->mock(UserDao::class);
        $this->postDaoMock = $this->mock(PostDao::class);
    }

    public function testSaving()
    {
        $user = new User(1);
        $user->setName('olivia');

        $post = new Post(2);
        $post->setName('sword');

        $comment = new Comment();
        $comment->setUser($user);
        $comment->setPost($post);
        $comment->setCreationTime(date('c'));
        $comment->setLastEditTime(date('c'));
        $comment->setText('whatever');
        $commentDao = $this->getCommentDao();
        $commentDao->save($comment);

        $this->userDaoMock->expects($this->once())->method('findById')->with(1)->willReturn($user);
        $this->postDaoMock->expects($this->once())->method('findById')->with(2)->willReturn($post);

        $savedComment = $commentDao->findById($comment->getId());
        $this->assertEquals(1, $savedComment->getUserId());
        $this->assertEquals(2, $savedComment->getPostId());
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

    public function findByPost(Post $post)
    {
        return $this->findOneBy('postId', $post->getId());
    }

    private function getCommentDao()
    {
        return new CommentDao(
            $this->databaseConnection,
            $this->userDaoMock,
            $this->postDaoMock);
    }
}
