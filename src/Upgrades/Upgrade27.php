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
				x INTEGER NOT NULL,
				y INTEGER NOT NULL,
				height INTEGER NOT NULL,
				width INTEGER NOT NULL,
				text TEXT NOT NULL
			)');
	}
}
