<?php
namespace Szurubooru\Upgrades;

class Upgrade09 implements IUpgrade
{
	private $postDao;
	private $historyService;

	public function __construct(
		\Szurubooru\Dao\PostDao $postDao,
		\Szurubooru\Services\HistoryService $historyService)
	{
		$this->postDao = $postDao;
		$this->historyService = $historyService;
	}

	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

		$pdo->exec('DROP TABLE IF EXISTS snapshots');

		$pdo->exec('CREATE TABLE snapshots
			(
				id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
				time DATETIME NOT NULL,
				type INTEGER NOT NULL,
				primaryKey TEXT NOT NULL,
				operation INTEGER NOT NULL,
				userId INTEGER,
				data BLOB,
				dataDifference BLOB
			)');

		foreach ($this->postDao->findAll() as $post)
		{
			$this->historyService->saveSnapshot($this->historyService->getPostChangeSnapshot($post));
		}
	}
}
