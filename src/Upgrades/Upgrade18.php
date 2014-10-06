<?php
namespace Szurubooru\Upgrades;

class Upgrade18 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('ALTER TABLE tags ADD COLUMN creationTime TIMESTAMP NOT NULL DEFAULT 0');
	}
}
