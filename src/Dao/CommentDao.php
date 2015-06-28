<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\CommentEntityConverter;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Comment;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Post;

class CommentDao extends AbstractDao implements ICrudDao
{
    private $userDao;
    private $postDao;

    public function __construct(
        DatabaseConnection $databaseConnection,
        UserDao $userDao,
        PostDao $postDao)
    {
        parent::__construct(
            $databaseConnection,
            'comments',
            new CommentEntityConverter());

        $this->userDao = $userDao;
        $this->postDao = $postDao;
    }

    public function findByPost(Post $post)
    {
        return $this->findBy('postId', $post->getId());
    }

    protected function afterLoad(Entity $comment)
    {
        $comment->setLazyLoader(
            Comment::LAZY_LOADER_USER,
            function (Comment $comment)
            {
                return $this->userDao->findById($comment->getUserId());
            });

        $comment->setLazyLoader(
            Comment::LAZY_LOADER_POST,
            function (Comment $comment)
            {
                return $this->postDao->findById($comment->getPostId());
            });
    }
}
