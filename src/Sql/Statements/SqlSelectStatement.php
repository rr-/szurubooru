<?php
class SqlSelectStatement extends SqlStatement
{
	const ORDER_ASC = 1;
	const ORDER_DESC = 2;

	protected $columns = null;
	protected $source = null;
	protected $innerJoins = [];
	protected $outerJoins = [];
	protected $criterion = null;
	protected $orderBy = [];
	protected $limit = null;
	protected $offset = null;
	protected $groupBy = null;

	public function getColumns()
	{
		return $this->columns;
	}

	public function resetColumns()
	{
		$this->columns = [];
		return $this;
	}

	public function setColumn($what)
	{
		$this->setColumns([$what]);
		return $this;
	}

	public function addColumn($what)
	{
		$this->columns []= $this->attachExpression($what);
		return $this;
	}

	public function setColumns($what)
	{
		$this->resetColumns();
		foreach ($what as $item)
			$this->addColumn($item);
		return $this;
	}

	public function getTable()
	{
		return $this->source;
	}

	public function setTable($table)
	{
		$this->source = new SqlStringExpression($table);
		return $this;
	}

	public function setSource(SqlExpression $source)
	{
		$this->source = $this->attachExpression($source);
		return $this;
	}

	public function addInnerJoin($table, SqlExpression $expression)
	{
		$this->innerJoins []= [$table, $this->attachExpression($expression)];
		return $this;
	}

	public function addOuterJoin($table, $expression)
	{
		$this->innerJoins []= [$table, $this->attachExpression($expression)];
		return $this;
	}

	public function getCriterion()
	{
		return $this->criterion;
	}

	public function setCriterion($criterion)
	{
		$this->criterion = $this->attachExpression($criterion);
		return $this;
	}

	public function resetOrderBy()
	{
		$this->orderBy = [];
		return $this;
	}

	public function setOrderBy($what, $dir = self::ORDER_ASC)
	{
		$this->resetOrderBy();
		$this->addOrderBy($this->attachExpression($what), $dir);
		return $this;
	}

	public function addOrderBy($what, $dir = self::ORDER_ASC)
	{
		$this->orderBy []= [$this->attachExpression($what), $dir];
		return $this;
	}

	public function getOrderBy()
	{
		return $this->orderBy;
	}

	public function resetLimit()
	{
		$this->limit = null;
		$this->offset = null;
		return $this;
	}

	public function setLimit($limit, $offset = null)
	{
		$this->limit = $this->attachExpression($limit);
		$this->offset = $this->attachExpression($offset);
		return $this;
	}

	public function groupBy($groupBy)
	{
		$this->groupBy = $this->attachExpression($groupBy);
	}

	public function getAsString()
	{
		$sql = 'SELECT ';
		if (!empty($this->columns))
			$sql .= join(', ', array_map(function($column)
			{
				return $column->getAsString();
			}, $this->columns));
		else
			$sql .= '1';
		$sql .= ' FROM (' . $this->source->getAsString() . ')';

		foreach ($this->innerJoins as $join)
		{
			list ($table, $criterion) = $join;
			$sql .= ' INNER JOIN ' . $table . ' ON ' . $criterion->getAsString();
		}

		foreach ($this->outerJoins as $outerJoin)
		{
			list ($table, $criterion) = $join;
			$sql .= ' OUTER JOIN ' . $table . ' ON ' . $criterion->getAsString();
		}

		if (!empty($this->criterion) and !empty($this->criterion->getAsString()))
			$sql .= ' WHERE ' . $this->criterion->getAsString();

		if (!empty($this->groupBy) and !empty($this->groupBy->getAsString()))
		{
			$sql .= ' GROUP BY ' . $this->groupBy->getAsString();
		}

		if (!empty($this->orderBy))
		{
			$f = true;
			foreach ($this->orderBy as $orderBy)
			{
				$sql .= $f ? ' ORDER BY' : ', ';
				$f = false;
				list ($orderColumn, $orderDir) = $orderBy;
				$sql .= ' ' . $orderColumn->getAsString();
				switch ($orderDir)
				{
					case self::ORDER_DESC:
						$sql .= ' DESC';
						break;
					case self::ORDER_ASC:
						$sql .= ' ASC';
						break;
				}
			}
		}

		if (!empty($this->limit) and !empty($this->limit->getAsString()))
		{
			$sql .= ' LIMIT ';
			$sql .= $this->limit->getAsString();
			if (!empty($this->offset))
			{
				$sql .= ' OFFSET ';
				$sql .= $this->offset->getAsString();
			}
		}

		return $sql;
	}
}
