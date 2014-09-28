<?php
namespace Szurubooru\Services;

class PostScoreService
{
	private $postScoreDao;
	private $favoritesDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		\Szurubooru\Dao\PostScoreDao $postScoreDao,
		\Szurubooru\Dao\FavoritesDao $favoritesDao,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->postScoreDao = $postScoreDao;
		$this->favoritesDao = $favoritesDao;
		$this->userDao = $userDao;
		$this->transactionManager = $transactionManager;
		$this->timeService = $timeService;
	}

	public function getScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$transactionFunc = function() use ($user, $post)
		{
			return $this->postScoreDao->getScore($user, $post);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getScoreValue(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$score = $this->getScore($user, $post);
		if (!$score)
			return 0;
		return $score->getScore();
	}

	public function setScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post, $scoreValue)
	{
		if ($scoreValue !== 1 and $scoreValue !== 0 and $scoreValue !== -1)
			throw new \DomainException('Bad score');

		$transactionFunc = function() use ($user, $post, $scoreValue)
		{
			if ($scoreValue !== 1)
				$this->favoritesDao->delete($user, $post);

			return $this->postScoreDao->setScore($user, $post, $scoreValue);
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
