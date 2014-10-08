<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\ScoreEntityConverter;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Comment;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Score;
use Szurubooru\Entities\User;
use Szurubooru\Services\TimeService;

class ScoreDao extends AbstractDao implements ICrudDao
{
	private $timeService;

	public function __construct(
		DatabaseConnection $databaseConnection,
		TimeService $timeService)
	{
		parent::__construct(
			$databaseConnection,
			'scores',
			new ScoreEntityConverter());

		$this->timeService = $timeService;
	}

	public function getScore(User $user, Entity $entity)
	{
		$query = $this->fpdo->from($this->tableName)->where('userId', $user->getId());

		if ($entity instanceof Post)
			$query->where('postId', $entity->getId());
		elseif ($entity instanceof Comment)
			$query->where('commentId', $entity->getId());
		else
			throw new \InvalidArgumentException();

		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		return array_shift($entities);
	}

	public function setScore(User $user, Entity $entity, $scoreValue)
	{
		$score = $this->getScore($user, $entity);
		if (!$score)
		{
			$score = new Score();
			$score->setTime($this->timeService->getCurrentTime());
			$score->setUserId($user->getId());

			if ($entity instanceof Post)
				$score->setPostId($entity->getId());
			elseif ($entity instanceof Comment)
				$score->setCommentId($entity->getId());
			else
				throw new \InvalidArgumentException();
		}
		$score->setScore($scoreValue);
		$this->save($score);
		return $score;
	}
}
