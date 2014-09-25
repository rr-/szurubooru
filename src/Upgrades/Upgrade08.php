<?php
namespace Szurubooru\Upgrades;

class Upgrade08 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('CREATE TABLE postRelations
			(
				id INTEGER PRIMARY KEY NOT NULL,
				post1id INTEGER NOT NULL,
				post2id INTEGER NOT NULL,
				UNIQUE (post1id, post2id)
			)');
	}
}

