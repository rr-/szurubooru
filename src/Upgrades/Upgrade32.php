<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade32 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('
            UPDATE snapshots SET
                data = REPLACE(data, \'"category":null\', \'"category":"default"\'),
                dataDifference = REPLACE(dataDifference, \'"category",null\', \'"category","default"\')');

        $pdo->exec('UPDATE tags SET category = \'default\' WHERE category IS NULL');
        $pdo->exec('ALTER TABLE tags CHANGE category category VARCHAR(25) NOT NULL DEFAULT \'default\'');
    }
}
