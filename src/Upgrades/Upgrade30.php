<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade30 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('
            CREATE TABLE sequencer (
                tableName VARCHAR(32) NOT NULL,
                lastUsedId INT(11) NOT NULL DEFAULT 0
            )');

        $tables = [
            'favorites',
            'tags',
            'comments',
            'globals',
            'postNotes',
            'posts',
            'scores',
            'snapshots',
            'tags',
            'tokens',
            'users'];

        foreach ($tables as $table)
        {
            $this->removeAutoIncrement($pdo, $table);
            $this->createSequencer($pdo, $table);
        }
    }

    private function removeAutoIncrement($pdo, $table)
    {
        $pdo->exec('ALTER TABLE ' . $table . ' CHANGE id id INT(11) UNSIGNED NOT NULL');
    }

    private function createSequencer($pdo, $table)
    {
        $pdo->exec(sprintf('
            INSERT INTO sequencer (tableName, lastUsedId)
            VALUES (\'%s\', IFNULL((SELECT MAX(id) FROM %s), 0))', $table, $table));
    }
}
