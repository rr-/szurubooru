<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade36 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('ALTER TABLE postNotes MODIFY x DOUBLE(7,3)');
        $pdo->exec('ALTER TABLE postNotes MODIFY y DOUBLE(7,3)');
        $pdo->exec('ALTER TABLE postNotes MODIFY width DOUBLE(7,3)');
        $pdo->exec('ALTER TABLE postNotes MODIFY height DOUBLE(7,3)');
    }
}
