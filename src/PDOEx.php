<?php
namespace Szurubooru;

class PDOEx extends \PDO
{
	private $queryCount = 0;

	public function prepare($statement, $driverOptions = [])
	{
		++ $this->queryCount;
		return parent::prepare($statement, $driverOptions);
	}

	public function getQueryCount()
	{
		return $this->queryCount;
	}
}
