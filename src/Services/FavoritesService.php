<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\FavoritesDao;
use Szurubooru\Dao\ScoreDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;

class FavoritesService
{
	private $favoritesDao;
	private $scoreDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		FavoritesDao $favoritesDao,
		ScoreDao $scoreDao,
		UserDao $userDao,
		TransactionManager $transactionManager,
		TimeService $timeService)
	{
		$this->favoritesDao = $favoritesDao;
		$this->scoreDao = $scoreDao;
		$this->userDao = $userDao;
		$this->transactionManager = $transactionManager;
		$this->timeService = $timeService;
	}

	public function getFavoriteUsers(Entity $entity)
	{
		$transactionFunc = function() use ($entity)
		{
			$favorites = $this->favoritesDao->findByEntity($entity);
			$userIds = [];
			foreach ($favorites as $favorite)
			{
				$userIds[] = $favorite->getUserId();
			}
			return $this->userDao->findByIds($userIds);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function addFavorite(User $user, Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			$this->scoreDao->setUserScore($user, $entity, 1);

			return $this->favoritesDao->set($user, $entity);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deleteFavorite(User $user, Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			$this->favoritesDao->delete($user, $entity);
		};
		$this->transactionManager->commit($transactionFunc);
	}
}
