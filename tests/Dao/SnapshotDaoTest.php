<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\SnapshotDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\User;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class SnapshotDaoTest extends AbstractDatabaseTestCase
{
    public function testSaving()
    {
        $snapshotDao = Injector::get(SnapshotDao::class);

        $snapshot = $this->getTestSnapshot();
        $snapshotDao->save($snapshot);

        $this->assertNotNull($snapshot->getId());
        $this->assertEntitiesEqual($snapshot, $snapshotDao->findById($snapshot->getId()));
    }

    public function testUserLazyLoader()
    {
        $userDao = Injector::get(UserDao::class);
        $snapshotDao = Injector::get(SnapshotDao::class);

        $user = self::getTestUser('victoria');
        $userDao->save($user);

        $snapshot = $this->getTestSnapshot();
        $snapshot->setUser($user);
        $snapshotDao->save($snapshot);

        $savedSnapshot = $snapshotDao->findById($snapshot->getId());
        $this->assertNotNull($savedSnapshot->getUserId());
        $this->assertEntitiesEqual($user, $savedSnapshot->getUser());
}

    private function getTestSnapshot()
    {
        $snapshot = new Snapshot();
        $snapshot->setType(Snapshot::TYPE_POST);
        $snapshot->setData(['wake up', 'neo', ['follow' => 'white rabbit']]);
        $snapshot->setPrimaryKey(1);
        $snapshot->setTime(date('c', mktime(1, 2, 3)));
        $snapshot->setUserId(null);
        $snapshot->setOperation(Snapshot::OPERATION_CHANGE);
        return $snapshot;
    }
}
