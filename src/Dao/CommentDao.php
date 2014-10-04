<?php
namespace Szurubooru\Dao;

class CommentDao extends AbstractDao implements ICrudDao
{
	private $userDao;
	private $postDao;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\PostDao $postDao)
	{
		parent::__construct(
			$databaseConnection,
			'comments',
			new \Szurubooru\Dao\EntityConverters\CommentEntityConverter());

		$this->userDao = $userDao;
		$this->postDao = $postDao;
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findBy('postId', $post->getId());
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $comment)
	{
		$comment->setLazyLoader(
			\Szurubooru\Entities\Comment::LAZY_LOADER_USER,
			function (\Szurubooru\Entities\Comment $comment)
			{
				return $this->userDao->findById($comment->getUserId());
			});

		$comment->setLazyLoader(
			\Szurubooru\Entities\Comment::LAZY_LOADER_POST,
			function (\Szurubooru\Entities\Comment $comment)
			{
				return $this->postDao->findById($comment->getPostId());
			});
	}
}
