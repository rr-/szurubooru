<?php
namespace Szurubooru\Services;

class FavoritesService
{
	private $favoritesDao;
	private $scoreDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		\Szurubooru\Dao\FavoritesDao $favoritesDao,
		\Szurubooru\Dao\ScoreDao $scoreDao,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->favoritesDao = $favoritesDao;
		$this->scoreDao = $scoreDao;
		$this->userDao = $userDao;
		$this->transactionManager = $transactionManager;
		$this->timeService = $timeService;
	}

	public function getFavoriteUsers(\Szurubooru\Entities\Entity $entity)
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

	public function addFavorite(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			$this->scoreDao->setScore($user, $entity, 1);

			return $this->favoritesDao->set($user, $entity);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deleteFavorite(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			$this->favoritesDao->delete($user, $entity);
		};
		$this->transactionManager->commit($transactionFunc);
	}
}
