<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade21 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$pdo->exec('UPDATE users SET accessRank = accessRank + 1 WHERE accessRank > 1');
	}
}
