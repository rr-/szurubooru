<?php
namespace Szurubooru\Dao;

class FavoritesDao extends AbstractDao implements ICrudDao
{
	private $userDao;
	private $postDao;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\PostDao $postDao)
	{
		parent::__construct(
			$databaseConnection,
			'favorites',
			new \Szurubooru\Dao\EntityConverters\FavoriteEntityConverter());

		$this->userDao = $userDao;
		$this->postDao = $postDao;
	}

	public function findByUserAndPost(\Szurubooru\Entities\User $user, \Szurubooru\Entities\Post $post)
	{
		$query = $this->fpdo->from($this->tableName)
			->where('userId', $user->getId())
			->where('postId', $post->getId());
		$arrayEntities = iterator_to_array($query);
		$entities = $this->arrayToEntities($arrayEntities);
		return array_shift($entities);
	}

	public function findByPost(\Szurubooru\Entities\Post $post)
	{
		return $this->findBy('postId', $post->getId());
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
}
