<?php
namespace Szurubooru\Services;
use Szurubooru\Dao\FavoritesDao;
use Szurubooru\Dao\ScoreDao;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;

class ScoreService
{
	private $scoreDao;
	private $favoritesDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		ScoreDao $scoreDao,
		FavoritesDao $favoritesDao,
		UserDao $userDao,
		TransactionManager $transactionManager,
		TimeService $timeService)
	{
		$this->scoreDao = $scoreDao;
		$this->favoritesDao = $favoritesDao;
		$this->userDao = $userDao;
		$this->transactionManager = $transactionManager;
		$this->timeService = $timeService;
	}

	public function getScoreValue(Entity $entity)
	{
		$transactionFunc = function() use ($entity)
		{
			return $this->scoreDao->getScoreValue($entity);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getUserScore(User $user, Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			return $this->scoreDao->getUserScore($user, $entity);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getUserScoreValue(User $user, Entity $entity)
	{
		$score = $this->getUserScore($user, $entity);
		if (!$score)
			return 0;
		return $score->getScore();
	}

	public function setUserScore(User $user, Entity $entity, $scoreValue)
	{
		if ($scoreValue !== 1 && $scoreValue !== 0 && $scoreValue !== -1)
			throw new \DomainException('Bad score');

		$transactionFunc = function() use ($user, $entity, $scoreValue)
		{
			if (($scoreValue !== 1) && ($entity instanceof Post))
				$this->favoritesDao->delete($user, $entity);

			return $this->scoreDao->setUserScore($user, $entity, $scoreValue);
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
