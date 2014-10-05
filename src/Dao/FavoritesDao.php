<?php
namespace Szurubooru\Dao;

class FavoritesDao extends AbstractDao implements ICrudDao
{
	private $userDao;
	private $postDao;
	private $timeService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\PostDao $postDao,
		\Szurubooru\Services\TimeService $timeService)
	{
		parent::__construct(
			$databaseConnection,
			'favorites',
			new \Szurubooru\Dao\EntityConverters\FavoriteEntityConverter());

		$this->userDao = $userDao;
		$this->postDao = $postDao;
		$this->timeService = $timeService;
	}

	public function findByEntity(\Szurubooru\Entities\Entity $entity)
	{
		if ($entity instanceof \Szurubooru\Entities\Post)
			return $this->findBy('postId', $entity->getId());
		else
			throw new \InvalidArgumentException();
	}

	public function set(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$favorite = $this->get($user, $entity);
		if (!$favorite)
		{
			$favorite = new \Szurubooru\Entities\Favorite();
			$favorite->setTime($this->timeService->getCurrentTime());
			$favorite->setUser($user);

			if ($entity instanceof \Szurubooru\Entities\Post)
				$favorite->setPost($entity);
			else
				throw new \InvalidArgumentException();

			$this->save($favorite);
		}
		return $favorite;
	}

	public function delete(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$favorite = $this->get($user, $entity);
		if ($favorite)
			$this->deleteById($favorite->getId());
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $favorite)
	{
		$favorite->setLazyLoader(
			\Szurubooru\Entities\Favorite::LAZY_LOADER_USER,
			function (\Szurubooru\Entities\Favorite $favorite)
			{
				return $this->userDao->findById($favorite->getUserId());
			});

		$favorite->setLazyLoader(
			\Szurubooru\Entities\Favorite::LAZY_LOADER_POST,
			function (\Szurubooru\Entities\Favorite $favorite)
			{
				return $this->postDao->findById($favorite->getPostId());
			});
	}

	private function get(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$query = $this->fpdo->from($this->tableName)->where('userId', $user->getId());

		if ($entity instanceof \Szurubooru\Entities\Post)
			$query->where('postId', $entity->getId());
		else
			throw new \InvalidArgumentException();

		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		return array_shift($entities);
	}
}
