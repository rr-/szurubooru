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

	public function getScore(User $user, Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			return $this->scoreDao->getScore($user, $entity);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getScoreValue(User $user, Entity $entity)
	{
		$score = $this->getScore($user, $entity);
		if (!$score)
			return 0;
		return $score->getScore();
	}

	public function setScore(User $user, Entity $entity, $scoreValue)
	{
		if ($scoreValue !== 1 and $scoreValue !== 0 and $scoreValue !== -1)
			throw new \DomainException('Bad score');

		$transactionFunc = function() use ($user, $entity, $scoreValue)
		{
			if (($scoreValue !== 1) and ($entity instanceof Post))
				$this->favoritesDao->delete($user, $entity);

			return $this->scoreDao->setScore($user, $entity, $scoreValue);
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
