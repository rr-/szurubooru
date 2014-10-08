<?php
namespace Szurubooru\Upgrades;
use Szurubooru\Dao\PostDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Services\HistoryService;

class Upgrade09 implements IUpgrade
{
	private $postDao;
	private $historyService;

	public function __construct(
		PostDao $postDao,
		HistoryService $historyService)
	{
		$this->postDao = $postDao;
		$this->historyService = $historyService;
	}

	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $databaseConnection->getDriver();

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
