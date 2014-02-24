<?php
abstract class SqlBinaryOperatorFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		throw new Exception('Not implemented');
	}

	public function getArgumentCount()
	{
		return 2;
	}

	public function getAsString()
	{
		return self::surroundBraces($this->subjects[0])
			. ' ' . $this->getOperator()
			. ' ' . self::surroundBraces($this->subjects[1]);
	}

	protected abstract function getOperator();
}
