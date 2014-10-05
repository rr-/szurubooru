<?php
namespace Szurubooru\Dao;

class ScoreDao extends AbstractDao implements ICrudDao
{
	private $timeService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Services\TimeService $timeService)
	{
		parent::__construct(
			$databaseConnection,
			'scores',
			new \Szurubooru\Dao\EntityConverters\ScoreEntityConverter());

		$this->timeService = $timeService;
	}

	public function getScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity)
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

	public function setScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Entity $entity, $scoreValue)
	{
		$score = $this->getScore($user, $entity);
		if (!$score)
		{
			$score = new \Szurubooru\Entities\Score();
			$score->setTime($this->timeService->getCurrentTime());
			$score->setUserId($user->getId());

			if ($entity instanceof \Szurubooru\Entities\Post)
				$score->setPostId($entity->getId());
			else
				throw new \InvalidArgumentException();
		}
		$score->setScore($scoreValue);
		$this->save($score);
		return $score;
	}
}
