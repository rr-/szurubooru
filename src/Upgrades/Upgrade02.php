<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade02 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $databaseConnection->getPDO()->exec('
            ALTER TABLE users ADD COLUMN accountConfirmed BOOLEAN NOT NULL DEFAULT FALSE');
    }
}
