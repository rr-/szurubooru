<?php
namespace Szurubooru\Upgrades;

class Upgrade12 implements IUpgrade
{
	private $tagService;

	public function __construct(\Szurubooru\Services\TagService $tagService)
	{
		$this->tagService = $tagService;
	}

	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$this->tagService->exportJson();
	}
}
