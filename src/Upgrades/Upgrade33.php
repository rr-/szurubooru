<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade33 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('UPDATE snapshots SET operation = 2 WHERE operation = 1');
		$pdo->exec('UPDATE snapshots SET operation = 1 WHERE operation = 0');
		$pdo->exec('CREATE INDEX tempIndex ON snapshots(primaryKey)');
		$pdo->exec('CREATE TABLE tempTable AS SELECT MIN(id) AS id FROM snapshots GROUP BY type, primaryKey');
		$pdo->exec('DROP INDEX tempIndex ON snapshots');
		$pdo->exec('UPDATE snapshots SET operation = 0 WHERE id IN (SELECT id FROM tempTable)');
		$pdo->exec('DROP TABLE tempTable');
	}
}
