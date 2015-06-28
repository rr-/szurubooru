<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\PostNoteEntityConverter;
use Szurubooru\DatabaseConnection;
use Szurubooru\Dao\PostDao;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\PostNote;

class PostNoteDao extends AbstractDao implements ICrudDao
{
    private $postDao;

    public function __construct(
        DatabaseConnection $databaseConnection,
        PostDao $postDao)
    {
        parent::__construct(
            $databaseConnection,
            'postNotes',
            new PostNoteEntityConverter());

        $this->postDao = $postDao;
    }

    public function findByPostId($postId)
    {
        return $this->findBy('postId', $postId);
    }

    protected function afterLoad(Entity $postNote)
    {
        $postNote->setLazyLoader(
            PostNote::LAZY_LOADER_POST,
            function (PostNote $postNote)
            {
                return $this->postDao->findById($postNote->getPostId());
            });
    }
}
