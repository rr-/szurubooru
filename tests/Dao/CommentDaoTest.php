<?php
namespace Szurubooru\Tests\Dao;

class CommentDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $userDaoMock;
	private $postDaoMock;

	public function setUp()
	{
		parent::setUp();
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->postDaoMock = $this->mock(\Szurubooru\Dao\PostDao::class);
	}

	public function testSaving()
	{
		$user = new \Szurubooru\Entities\User(1);
		$user->setName('olivia');

		$post = new \Szurubooru\Entities\Post(2);
		$post->setName('sword');

		$comment = new \Szurubooru\Entities\Comment();
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
		$userDao = \Szurubooru\Injector::get(\Szurubooru\Dao\UserDao::class);
		$postDao = \Szurubooru\Injector::get(\Szurubooru\Dao\PostDao::class);
		$commentDao = \Szurubooru\Injector::get(\Szurubooru\Dao\CommentDao::class);

		$user = self::getTestUser('olivia');
		$userDao->save($user);

		$post = self::getTestPost();
		$postDao->save($post);

		$this->assertEquals(0, $post->getCommentCount());
		$this->assertNotNull($post->getId());

		$comment = new \Szurubooru\Entities\Comment();
		$comment->setUser($user);
		$comment->setPost($post);
		$comment->setCreationTime(date('c'));
		$comment->setLastEditTime(date('c'));
		$comment->setText('whatever');
		$commentDao->save($comment);

		$post = $postDao->findById($post->getId());
		$this->assertNotNull($post);
		$this->assertEquals(1, $post->getCommentCount());

		return [$postDao, $commentDao, $post, $comment];
	}

	/**
	 * @depends testPostMetadataSyncInsert
	 */
	public function testPostMetadataSyncDelete($args)
	{
		list ($postDao, $commentDao, $post, $comment) = $args;

		$commentDao->deleteById($comment->getId());

		$post = $postDao->findById($post->getId());
		$this->assertNotNull($post);
		$this->assertEquals(0, $post->getCommentCount());
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findOneBy('postId', $post->getId());
	}

	private function getCommentDao()
	{
		return new \Szurubooru\Dao\CommentDao(
			$this->databaseConnection,
			$this->userDaoMock,
			$this->postDaoMock);
	}
}
