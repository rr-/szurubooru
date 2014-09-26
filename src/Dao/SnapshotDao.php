<?php
namespace Szurubooru\Dao;

class SnapshotDao extends AbstractDao
{
	private $userDao;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\UserDao $userDao)
	{
		parent::__construct(
			$databaseConnection,
			'snapshots',
			new \Szurubooru\Dao\EntityConverters\SnapshotEntityConverter());

		$this->userDao = $userDao;
	}

	public function findByTypeAndKey($type, $primaryKey)
	{
		$query = $this->fpdo
			->from($this->tableName)
			->where('type', $type)
			->where('primaryKey', $primaryKey)
			->orderBy('time DESC');
		return $this->arrayToEntities(iterator_to_array($query));
	}

	public function afterLoad(\Szurubooru\Entities\Entity $snapshot)
	{
		$snapshot->setLazyLoader(
			\Szurubooru\Entities\Snapshot::LAZY_LOADER_USER,
			function (\Szurubooru\Entities\Snapshot $snapshot)
			{
				return $this->getUser($snapshot);
			});
	}

	private function getUser(\Szurubooru\Entities\Snapshot $snapshot)
	{
		$userId =  $snapshot->getUserId();
		return $this->userDao->findById($userId);
	}
}
