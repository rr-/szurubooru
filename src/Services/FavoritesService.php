<?php
namespace Szurubooru\Services;

class FavoritesService
{
	private $favoritesDao;
	private $postScoreDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		\Szurubooru\Dao\FavoritesDao $favoritesDao,
		\Szurubooru\Dao\PostScoreDao $postScoreDao,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->favoritesDao = $favoritesDao;
		$this->postScoreDao = $postScoreDao;
		$this->userDao = $userDao;
		$this->transactionManager = $transactionManager;
		$this->timeService = $timeService;
	}

	public function getFavoriteUsers(\Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($post)
		{
			$favorites = $this->favoritesDao->findByPost($post);
			$userIds = [];
			foreach ($favorites as $favorite)
			{
				$userIds[] = $favorite->getUserId();
			}
			return $this->userDao->findByIds($userIds);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function addFavorite(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($user, $post)
		{
			$this->postScoreDao->setScore($user, $post, 1);

			return $this->favoritesDao->set($user, $post);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deleteFavorite(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($user, $post)
		{
			$this->favoritesDao->delete($user, $post);
		};
		$this->transactionManager->commit($transactionFunc);
	}
}
