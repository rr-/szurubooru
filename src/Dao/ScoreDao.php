<?php
namespace Szurubooru\Dao;

class ScoreDao extends AbstractDao implements ICrudDao
{
	private $userDao;
	private $postDao;
	private $timeService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\PostDao $postDao,
		\Szurubooru\Services\TimeService $timeService)
	{
		parent::__construct(
			$databaseConnection,
			'scores',
			new \Szurubooru\Dao\EntityConverters\ScoreEntityConverter());

		$this->userDao = $userDao;
		$this->postDao = $postDao;
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
			$score->setUser($user);

			if ($entity instanceof \Szurubooru\Entities\Post)
				$score->setPost($entity);
			else
				throw new \InvalidArgumentException();
		}
		$score->setScore($scoreValue);
		$this->save($score);
		return $score;
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $score)
	{
		$score->setLazyLoader(
			\Szurubooru\Entities\Score::LAZY_LOADER_USER,
			function (\Szurubooru\Entities\Score $score)
			{
				return $this->userDao->findById($score->getUserId());
			});

		$score->setLazyLoader(
			\Szurubooru\Entities\Score::LAZY_LOADER_POST,
			function (\Szurubooru\Entities\Score $score)
			{
				return $this->postDao->findById($score->getPostId());
			});
	}
}
