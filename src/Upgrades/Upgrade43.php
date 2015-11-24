<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade43 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $pdo->exec('ALTER TABLE posts CHANGE COLUMN uploadTime creationTime TIMESTAMP NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE users CHANGE COLUMN registrationTime creationTime TIMESTAMP NOT NULL DEFAULT 0');
    }
}
