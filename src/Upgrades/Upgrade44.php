<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade44 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $pdo->exec('ALTER TABLE tags ADD COLUMN lastEditTime TIMESTAMP NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE tags SET lastEditTime = creationTime');
    }
}
