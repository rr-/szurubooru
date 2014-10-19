<?php
namespace Szurubooru\PDOEx;

class UpdateQuery extends BaseQuery
{
	private $values = [];

	public function set(array $values)
	{
		$this->values = $values;
		$this->refreshBaseQuery();
		return $this;
	}

	public function innerJoin($table, $condition)
	{
		throw new \BadMethodCallException('This makes no sense!');
	}

	protected function init()
	{
		$this->refreshBaseQuery();
	}

	private function refreshBaseQuery()
	{
		$sql = 'UPDATE ' . $this->table;
		$sql .= ' SET ';
		foreach ($this->values as $key => $value)
			$sql .= $key . ' = ' . $this->bind($value) . ', ';
		$sql = substr($sql, 0, -2);
		$this->clauses[self::CLAUSE_BASE] = $sql;
	}
}
