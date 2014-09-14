<?php
namespace Szurubooru\Upgrades;

interface IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection);
}
