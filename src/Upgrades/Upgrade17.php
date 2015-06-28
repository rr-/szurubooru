<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade17 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('ALTER TABLE users ADD COLUMN passwordSalt VARCHAR(32)');
        $pdo->exec('UPDATE users SET passwordSalt = "/"');
    }
}
