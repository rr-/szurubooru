<?php
namespace Szurubooru\PDOEx;

class SelectQuery extends BaseQuery implements \Countable
{
	private $selectTarget = '';
	private $orderTarget = '';
	private $offset = 0;
	private $limit = null;

	public function limit($limit)
	{
		if ($limit === null || $limit === false)
			$this->limit = null;
		else
			$this->limit = intval($limit);
		$this->refreshLimitClause();
		return $this;
	}

	public function offset($offset)
	{
		$this->offset = intval($offset);
		$this->refreshLimitClause();
		return $this;
	}

	public function select($key)
	{
		if ($key === null)
			$this->selectTarget = '';
		else
		{
			if ($this->selectTarget)
				$this->selectTarget .= ', ';
			$this->selectTarget .= $key;
		}
		$this->refreshBaseClause();
		return $this;
	}

	public function orderBy($key, $dir = null)
	{
		if ($key === null)
			$this->orderTarget = '';
		else
		{
			if ($this->orderTarget)
				$this->orderTarget .= ', ';
			$this->orderTarget .= rtrim($key . ' ' . $dir);
		}
		$this->refreshOrderClause();
		return $this;
	}

	public function groupBy($key)
	{
		$this->clauses[self::CLAUSE_GROUP] = 'GROUP BY ' . $key;
		return $this;
	}

	public function count()
	{
		$query = clone($this);
		return iterator_to_array($query->select(null)->select('COUNT(1) AS c'))[0]['c'];
	}

	protected function init()
	{
		$this->selectTarget = $this->table . '.*';
		$this->refreshBaseClause();
	}

	private function refreshBaseClause()
	{
		$this->clauses[self::CLAUSE_BASE] = 'SELECT ' . $this->selectTarget . ' FROM ' . $this->table;
	}

	private function refreshOrderClause()
	{
		$this->clauses[self::CLAUSE_ORDER] = 'ORDER BY ' . $this->orderTarget;
	}

	private function refreshLimitClause()
	{
		$sql = '';
		if ($this->limit !== null)
		{
			$sql .= 'LIMIT ' . $this->limit;
			if ($this->offset !== null && $this->offset !== 0)
				$sql .= ' OFFSET ' . intval($this->offset);
		}
		$this->clauses[self::CLAUSE_LIMIT] = $sql;
	}
}
