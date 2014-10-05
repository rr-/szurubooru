<?php
namespace Szurubooru\Services;

class ScoreService
{
	private $scoreDao;
	private $favoritesDao;
	private $userDao;
	private $transactionManager;
	private $timeService;

	public function __construct(
		\Szurubooru\Dao\ScoreDao $scoreDao,
		\Szurubooru\Dao\FavoritesDao $favoritesDao,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->scoreDao = $scoreDao;
		$this->favoritesDao = $favoritesDao;
		$this->userDao = $userDao;
		$this->transactionManager = $transactionManager;
		$this->timeService = $timeService;
	}

	public function getScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$transactionFunc = function() use ($user, $entity)
		{
			return $this->scoreDao->getScore($user, $entity);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getScoreValue(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
	{
		$score = $this->getScore($user, $entity);
		if (!$score)
			return 0;
		return $score->getScore();
	}

	public function setScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity, $scoreValue)
	{
		if ($scoreValue !== 1 and $scoreValue !== 0 and $scoreValue !== -1)
			throw new \DomainException('Bad score');

		$transactionFunc = function() use ($user, $entity, $scoreValue)
		{
			if (($scoreValue !== 1) and ($entity instanceof \Szurubooru\Entities\Post))
				$this->favoritesDao->delete($user, $entity);

			return $this->scoreDao->setScore($user, $entity, $scoreValue);
		};
		return $this->transactionManager->commit($transactionFunc);
	}
}
