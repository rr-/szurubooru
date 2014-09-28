<?php
namespace Szurubooru\Dao;

class FavoritesDao extends AbstractDao implements ICrudDao
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
			'favorites',
			new \Szurubooru\Dao\EntityConverters\FavoriteEntityConverter());

		$this->userDao = $userDao;
		$this->postDao = $postDao;
		$this->timeService = $timeService;
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findBy('postId', $post->getId());
	}

	public function set(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$favorite = $this->get($user, $post);
		if (!$favorite)
		{
			$favorite = new \Szurubooru\Entities\Favorite();
			$favorite->setUser($user);
			$favorite->setPost($post);
			$favorite->setTime($this->timeService->getCurrentTime());
			$this->save($favorite);
		}
		return $favorite;
	}

	public function delete(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$favorite = $this->get($user, $post);
		if ($favorite)
			$this->deleteById($favorite->getId());
	}

	protected function afterLoad(\Szurubooru\Entities\Entity $favorite)
	{
		$favorite->setLazyLoader(
			\Szurubooru\Entities\Favorite::LAZY_LOADER_USER,
			function (\Szurubooru\Entities\Favorite $favorite)
			{
				return $this->userDao->findById($favorite->getUserId());
			});

		$favorite->setLazyLoader(
			\Szurubooru\Entities\Favorite::LAZY_LOADER_POST,
			function (\Szurubooru\Entities\Favorite $favorite)
			{
				return $this->postDao->findById($favorite->getPostId());
			});
	}

	private function get(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$query = $this->fpdo->from($this->tableName)
			->where('userId', $user->getId())
			->where('postId', $post->getId());
		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		return array_shift($entities);
	}
}
