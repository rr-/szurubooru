<?php
class SqlQuery
{
	protected $sql;
	protected $bindings;

	public function __construct()
	{
		$this->sql = '';
		$this->bindings = [];
	}

	public function __call($name, array $arguments)
	{
		$name = TextHelper::camelCaseToKebabCase($name);
		$name = str_replace('-', ' ', $name);
		$this->sql .= $name . ' ';

		if (!empty($arguments))
		{
			$arg = array_shift($arguments);
			assert(empty($arguments));

			if (is_object($arg))
			{
				throw new Exception('Not implemented');
			}
			else
			{
				$this->sql .= $arg . ' ';
			}
		}

		return $this;
	}

	public function put($arg)
	{
		if (is_array($arg))
		{
			foreach ($arg as $key => $val)
			{
				if (is_numeric($key))
					$this->bindings []= $val;
				else
					$this->bindings[$key] = $val;
			}
		}
		else
		{
			$this->bindings []= $arg;
		}
		return $this;
	}

	public function raw($raw)
	{
		$this->sql .= $raw . ' ';
		return $this;
	}

	public function open()
	{
		$this->sql .= '(';
		return $this;
	}

	public function close()
	{
		$this->sql .= ') ';
		return $this;
	}

	public function surround($raw)
	{
		$this->sql .= '(' . $raw . ') ';
		return $this;
	}

	public function genSlots($bindings)
	{
		if (empty($bindings))
			return $this;
		$this->sql .= '(';
		$this->sql .= join(',', array_fill(0, count($bindings), '?'));
		$this->sql .= ') ';
		return $this;
	}

	public function getBindings()
	{
		return $this->bindings;
	}

	public function getSql()
	{
		return trim($this->sql);
	}
}
