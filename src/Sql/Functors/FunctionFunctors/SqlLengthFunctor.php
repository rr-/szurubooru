<?php
class SqlLengthFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'LENGTH';
	}

	public function getArgumentCount()
	{
		return 1;
	}
}
