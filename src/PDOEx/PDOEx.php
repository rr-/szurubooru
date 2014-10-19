<?php
namespace Szurubooru\PDOEx;

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

	public function from($table)
	{
		return new SelectQuery($this, $table);
	}

	public function insertInto($table)
	{
		return new InsertQuery($this, $table);
	}

	public function update($table)
	{
		return new UpdateQuery($this, $table);
	}

	public function deleteFrom($table)
	{
		return new DeleteQuery($this, $table);
	}
}
