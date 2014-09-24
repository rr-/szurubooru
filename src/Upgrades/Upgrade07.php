<?php
namespace Szurubooru\Upgrades;

class Upgrade07 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('ALTER TABLE posts ADD COLUMN featureCount INTEGER NOT NULL DEFAULT 0');
		$pdo->exec('ALTER TABLE posts ADD COLUMN lastFeatureTime TIMESTAMP');

		$pdo->exec('CREATE TABLE globals
			(
				id INTEGER PRIMARY KEY NOT NULL,
				key TEXT UNIQUE NOT NULL,
				value TEXT
			)');
	}
}
