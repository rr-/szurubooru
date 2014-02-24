<?php
class SqlAbsFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'ABS';
	}

	public function getArgumentCount()
	{
		return 1;
	}
}
