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
		$bindings = $this->bindings;
		foreach ($this->subExpressions as $subExpression)
			$bindings = array_merge($bindings, $subExpression->getBindings());
		return $bindings;
	}

	public function attachExpression($object)
	{
		if ($object instanceof SqlBinding)
		{
			$expr =  new SqlStringExpression($object->getName());
			$expr->bind($object->getName(), $object->getValue());
			$this->subExpressions []= $expr;
			return $expr;
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
