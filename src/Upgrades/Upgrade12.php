<?php
namespace Szurubooru\Upgrades;

class Upgrade12 implements IUpgrade
{
	private $tagDao;

	public function __construct(\Szurubooru\Dao\TagDao $tagDao)
	{
		$this->tagDao = $tagDao;
	}

	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$this->tagDao->exportJson();
	}
}
