<?php
class SqlInsertStatement extends SqlStatement
{
	protected $table;
	protected $columns;
	protected $source;

	public function getTable()
	{
		return $this->table;
	}

	public function setTable($table)
	{
		$this->table = new SqlStringExpression($table);
		return $this;
	}

	public function setColumn($column, $what)
	{
		$this->columns[$column] = $this->attachExpression($what);
		$this->source = null;
		return $this;
	}

	public function setSource($columns, $what)
	{
		$this->source = $this->attachExpression($what);
		$this->columns = $columns;
	}

	public function getAsString()
	{
		$sql = 'INSERT INTO ' . $this->table->getAsString() . ' ';
		if (!empty($this->source))
		{
			$sql .= ' (' . join(', ', $this->columns) . ')';
			$sql .= ' ' . $this->source->getAsString();
		}
		else
		{
			if (empty($this->columns))
			{
				$config = \Chibi\Registry::getConfig();
				if ($config->main->dbDriver == 'sqlite')
					$sql .= ' DEFAULT VALUES';
				else
					$sql .= ' VALUES()';
			}
			else
			{
				$sql .= ' (' . join(', ', array_keys($this->columns)) . ')';

				$sql .= ' VALUES (' . join(', ', array_map(function($val)
					{
						return '(' . $val->getAsString() . ')';
					}, array_values($this->columns))) . ')';
			}
		}
		return $sql;
	}
}
