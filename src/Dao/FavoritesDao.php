<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\FavoriteEntityConverter;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Favorite;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;

class FavoritesDao extends AbstractDao implements ICrudDao
{
	private $timeService;

	public function __construct(
		DatabaseConnection $databaseConnection,
		TimeService $timeService)
	{
		parent::__construct(
			$databaseConnection,
			'favorites',
			new FavoriteEntityConverter());

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

	private function get(User $user, Entity $entity)
	{
		$query = $this->fpdo->from($this->tableName)->where('userId', $user->getId());

		if ($entity instanceof Post)
			$query->where('postId', $entity->getId());
		else
			throw new \InvalidArgumentException();

		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		return array_shift($entities);
	}
}
