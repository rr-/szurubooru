<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;
use Szurubooru\Services\TagService;

class Upgrade12 implements IUpgrade
{
	private $tagService;

	public function __construct(TagService $tagService)
	{
		$this->tagService = $tagService;
	}

	public function run(DatabaseConnection $databaseConnection)
	{
		$this->tagService->exportJson();
	}
}
