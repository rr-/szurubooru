<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

interface IUpgrade
{
	public function run(DatabaseConnection $databaseConnection);
}
