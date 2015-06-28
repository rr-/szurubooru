<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\SnapshotEntityConverter;
use Szurubooru\Dao\UserDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\Snapshot;

class SnapshotDao extends AbstractDao
{
    private $userDao;

    public function __construct(
        DatabaseConnection $databaseConnection,
        UserDao $userDao)
    {
        parent::__construct(
            $databaseConnection,
            'snapshots',
            new SnapshotEntityConverter());

        $this->userDao = $userDao;
    }

    public function findEarlierSnapshots(Snapshot $snapshot)
    {
        $query = $this->pdo
            ->from($this->tableName)
            ->where('type', $snapshot->getType())
            ->where('primaryKey', $snapshot->getPrimaryKey())
            ->orderBy('time DESC');

        if ($snapshot->getId())
            $query->where('id < ?', $snapshot->getId());

        return $this->arrayToEntities(iterator_to_array($query));
    }

    public function afterLoad(Entity $snapshot)
    {
        $snapshot->setLazyLoader(
            Snapshot::LAZY_LOADER_USER,
            function (Snapshot $snapshot)
            {
                return $this->getUser($snapshot);
            });
    }

    private function getUser(Snapshot $snapshot)
    {
        $userId = $snapshot->getUserId();
        return $this->userDao->findById($userId);
    }
}
