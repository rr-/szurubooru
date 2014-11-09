<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade00 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$pdo->exec('CREATE TABLE executedUpgrades (number INT NOT NULL)');

		$oldFilePath = __DIR__ . '/../../data/executed_upgrades.txt';
		if (file_exists($oldFilePath))
		{
			foreach (explode("\n", file_get_contents($oldFilePath)) as $className)
			{
				if (preg_match('/(\d+)/', $className, $matches))
				{
					$number = intval($matches[1]);
					$pdo->insertInto('executedUpgrades')->values(['number' => $number])->execute();
				}
			}
			unlink($oldFilePath);
		}
	}
}
