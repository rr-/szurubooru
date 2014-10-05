<?php
namespace Szurubooru;

class PDOEx extends \PDO
{
	private $queryCount = 0;
	private $statements = [];

	public function prepare($statement, $driverOptions = [])
	{
		++ $this->queryCount;
		$this->statements[] = $statement;
		return parent::prepare($statement, $driverOptions);
	}

	public function getQueryCount()
	{
		return $this->queryCount;
	}

	public function getStatements()
	{
		return $this->statements;
	}
}
