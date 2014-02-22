<?php
class SqlUpdateStatement extends SqlStatement
{
	protected $table;
	protected $criterion;
	protected $columns;

	public function getTable()
	{
		return $this->table;
	}

	public function setTable($table)
	{
		$this->table = new SqlStringExpression($table);
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

	public function setColumn($column, $what)
	{
		$this->columns[$column] = $this->attachExpression($what);
		return $this;
	}

	public function getAsString()
	{
		$sql = 'UPDATE ' . $this->table->getAsString();

		if (!empty($this->columns))
		{
			$sql .= ' SET ' . join(', ', array_map(function($key)
				{
					return $key . ' = (' . $this->columns[$key]->getAsString() . ')';
				}, array_keys($this->columns)));
		}

		if (!empty($this->criterion) and !empty($this->criterion->getAsString()))
			$sql .= ' WHERE ' . $this->criterion->getAsString();

		return $sql;
	}
}
