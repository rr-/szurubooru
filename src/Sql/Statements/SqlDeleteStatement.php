<?php
class SqlDeleteStatement extends SqlStatement
{
	protected $table;
	protected $criterion;

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

	public function getAsString()
	{
		$sql = 'DELETE FROM ' . $this->table->getAsString() . ' ';

		if (!empty($this->criterion) and !empty($this->criterion->getAsString()))
			$sql .= ' WHERE ' . $this->criterion->getAsString();

		return $sql;
	}
}

