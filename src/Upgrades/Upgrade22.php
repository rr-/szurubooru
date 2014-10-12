<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade22 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $databaseConnection->getDriver();

		$pdo->exec('
			CREATE TABLE snapshots2 (
					id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
					time DATETIME NOT NULL,
					type INTEGER NOT NULL,
					primaryKey INTEGER NOT NULL,
					operation INTEGER NOT NULL,
					userId INTEGER DEFAULT NULL,
					data BLOB,
					dataDifference BLOB)');

		$pdo->exec('
			INSERT INTO snapshots2
				(id, time, type, primaryKey, operation, userId, data, dataDifference)
			SELECT
				id, time, type, primaryKey, operation, userId, data, dataDifference
			FROM snapshots');

		$pdo->exec('DROP TABLE snapshots');
		$pdo->exec('ALTER TABLE snapshots2 RENAME TO snapshots');

		$pdo->exec('CREATE INDEX idx_snapshots_time ON snapshots(time)');
		$pdo->exec('CREATE INDEX idx_snapshots_typePK ON snapshots(type, primaryKey)');
	}
}
