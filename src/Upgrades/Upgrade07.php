<?php
namespace Szurubooru\Upgrades;

class Upgrade07 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $databaseConnection->getDriver();

		$pdo->exec('CREATE TABLE globals
			(
				id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
				dataKey VARCHAR(32) UNIQUE NOT NULL,
				dataValue VARCHAR(64)
			)');

		$pdo->exec('ALTER TABLE posts ADD COLUMN featureCount INTEGER NOT NULL DEFAULT 0');
		$pdo->exec('ALTER TABLE posts ADD COLUMN lastFeatureTime DATETIME');
	}
}
