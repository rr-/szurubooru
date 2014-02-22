<?php
abstract class SqlExpression
{
	abstract public function getAsString();

	protected $bindings = [];
	protected $subExpressions = [];

	private function bind($key, $val)
	{
		$this->bindings[$key] = $val;
		return $this;
	}

	public function getBindings()
	{
		$stack = array_merge([], $this->subExpressions);
		$bindings = $this->bindings;
		while (!empty($stack))
		{
			$item = array_pop($stack);
			$stack = array_merge($stack, $item->subExpressions);
			$bindings = array_merge($bindings, $item->bindings);
		}
		return $bindings;
	}

	public function attachExpression($object)
	{
		if ($object instanceof SqlBinding)
		{
			$this->bind($object->getName(), $object->getValue());
			return new SqlStringExpression($object->getName());
		}
		else if ($object instanceof SqlExpression)
		{
			$this->subExpressions []= $object;
			return $object;
		}
		else
		{
			return new SqlStringExpression((string) $object);
		}
	}
}
