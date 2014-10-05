<?php
namespace Szurubooru\Dao;

class FavoritesDao extends AbstractDao implements ICrudDao
{
	private $timeService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Services\TimeService $timeService)
	{
		parent::__construct(
			$databaseConnection,
			'favorites',
			new \Szurubooru\Dao\EntityConverters\FavoriteEntityConverter());

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
			$favorite->setUserId($user->getId());

			if ($entity instanceof \Szurubooru\Entities\Post)
				$favorite->setPostId($entity->getId());
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
