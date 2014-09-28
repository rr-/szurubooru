<?php
namespace Szurubooru\Upgrades;

class Upgrade02 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$databaseConnection->getPDO()->exec('
			ALTER TABLE users ADD COLUMN accountConfirmed BOOLEAN NOT NULL DEFAULT FALSE');
	}
}
