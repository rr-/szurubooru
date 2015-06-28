<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade18 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('ALTER TABLE tags ADD COLUMN creationTime TIMESTAMP NOT NULL DEFAULT 0');
    }
}
