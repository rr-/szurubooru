<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\FavoriteEntityConverter;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Favorite;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;

class FavoritesDao extends AbstractDao implements ICrudDao
{
    private $userDao;
    private $postDao;
    private $timeService;

    public function __construct(
        DatabaseConnection $databaseConnection,
        UserDao $userDao,
        PostDao $postDao,
        TimeService $timeService)
    {
        parent::__construct(
            $databaseConnection,
            'favorites',
            new FavoriteEntityConverter());

        $this->userDao = $userDao;
        $this->postDao = $postDao;
        $this->timeService = $timeService;
    }

    public function findByEntity(Entity $entity)
    {
        if ($entity instanceof Post)
            return $this->findBy('postId', $entity->getId());
        else
            throw new \InvalidArgumentException();
    }

    public function set(User $user, Entity $entity)
    {
        $favorite = $this->get($user, $entity);
        if (!$favorite)
        {
            $favorite = new Favorite();
            $favorite->setTime($this->timeService->getCurrentTime());
            $favorite->setUserId($user->getId());

            if ($entity instanceof Post)
                $favorite->setPostId($entity->getId());
            else
                throw new \InvalidArgumentException();

            $this->save($favorite);
        }
        return $favorite;
    }

    public function delete(User $user, Entity $entity)
    {
        $favorite = $this->get($user, $entity);
        if ($favorite)
            $this->deleteById($favorite->getId());
    }

    protected function afterLoad(Entity $favorite)
    {
        $favorite->setLazyLoader(
            Favorite::LAZY_LOADER_USER,
            function (Favorite $favorite)
            {
                return $this->userDao->findById($favorite->getUserId());
            });

        $favorite->setLazyLoader(
            Favorite::LAZY_LOADER_POST,
            function (Favorite $favorite)
            {
                return $this->postDao->findById($favorite->getPostId());
            });
    }

    private function get(User $user, Entity $entity)
    {
        $query = $this->pdo->from($this->tableName)->where('userId', $user->getId());

        if ($entity instanceof Post)
            $query->where('postId', $entity->getId());
        else
            throw new \InvalidArgumentException();

        $arrayEntities = iterator_to_array($query);
        $entities = $this->arrayToEntities($arrayEntities);
        return array_shift($entities);
    }
}
