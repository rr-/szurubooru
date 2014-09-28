<?php
namespace Szurubooru\Services;

class FavoritesService
{
	private $favoritesDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		\Szurubooru\Dao\FavoritesDao $favoritesDao,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->favoritesDao = $favoritesDao;
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
			$favorite = $this->favoritesDao->findByUserAndPost($user, $post);
			if (!$favorite)
			{
				$favorite = new \Szurubooru\Entities\Favorite();
				$favorite->setUser($user);
				$favorite->setPost($post);
				$favorite->setTime($this->timeService->getCurrentTime());
				$this->favoritesDao->save($favorite);
			}
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deleteFavorite(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($user, $post)
		{
			$favorite = $this->favoritesDao->findByUserAndPost($user, $post);
			$this->favoritesDao->deleteById($favorite->getId());
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
