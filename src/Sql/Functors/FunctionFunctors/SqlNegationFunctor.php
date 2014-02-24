<?php
class SqlNegationFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'NOT';
	}

	public function getArgumentCount()
	{
		return 1;
	}
}
