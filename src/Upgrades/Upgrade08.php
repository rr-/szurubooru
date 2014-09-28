<?php
namespace Szurubooru\Upgrades;

class Upgrade08 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

		$pdo->exec('CREATE TABLE postRelations
			(
				id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
				post1id INTEGER NOT NULL,
				post2id INTEGER NOT NULL,
				UNIQUE (post1id, post2id)
			)');
	}
}
