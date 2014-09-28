<?php
namespace Szurubooru\Dao;

class PostScoreDao extends AbstractDao implements ICrudDao
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
			'postScores',
			new \Szurubooru\Dao\EntityConverters\PostScoreEntityConverter());

		$this->userDao = $userDao;
		$this->postDao = $postDao;
		$this->timeService = $timeService;
	}

	public function getScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$query = $this->fpdo->from($this->tableName)
			->where('userId', $user->getId())
			->where('postId', $post->getId());
		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		return array_shift($entities);
	}

	public function setScore(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post, $scoreValue)
	{
		$postScore = $this->getScore($user, $post);
		if (!$postScore)
		{
			$postScore = new \Szurubooru\Entities\PostScore();
			$postScore->setUser($user);
			$postScore->setPost($post);
			$postScore->setTime($this->timeService->getCurrentTime());
		}
		$postScore->setScore($scoreValue);
		$this->save($postScore);
		return $postScore;
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $postScore)
	{
		$postScore->setLazyLoader(
			\Szurubooru\Entities\PostScore::LAZY_LOADER_USER,
			function (\Szurubooru\Entities\PostScore $postScore)
			{
				return $this->userDao->findById($postScore->getUserId());
			});

		$postScore->setLazyLoader(
			\Szurubooru\Entities\PostScore::LAZY_LOADER_POST,
			function (\Szurubooru\Entities\PostScore $postScore)
			{
				return $this->postDao->findById($postScore->getPostId());
			});
	}
}
