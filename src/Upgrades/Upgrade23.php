<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;
use Szurubooru\Services\TagService;

class Upgrade23 implements IUpgrade
{
	private $tagService;

	public function __construct(TagService $tagService)
	{
		$this->tagService = $tagService;
	}

	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$pdo->exec('ALTER TABLE tags ADD COLUMN banned BOOLEAN NOT NULL DEFAULT 0');

		$this->tagService->exportJson();
	}
}
