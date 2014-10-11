<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade20 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$pdo->exec('CREATE INDEX idx_postTags_tagId ON postTags(tagId)');
	}
}
