<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade27 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $databaseConnection->getDriver();

		$pdo->exec(
			'CREATE TABLE postNotes (
				id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
				postId INTEGER NOT NULL,
				x DECIMAL(6,2) NOT NULL,
				y DECIMAL(6,2) NOT NULL,
				height DECIMAL(6,2) NOT NULL,
				width DECIMAL(6,2) NOT NULL,
				text TEXT NOT NULL
			)');
	}
}
