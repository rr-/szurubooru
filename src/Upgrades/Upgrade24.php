<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade24 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $driver = $databaseConnection->getDriver();

        $pdo->exec(
            'CREATE TABLE tagRelations (
                id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
                tag1id INTEGER NOT NULL,
                tag2id INTEGER NOT NULL,
                type INTEGER(2) NOT NULL,
                UNIQUE (tag1id, tag2id, type)
            )');
    }
}
