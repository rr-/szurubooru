<?php
class SqlIfNullFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'IFNULL';
	}

	public function getArgumentCount()
	{
		return 2;
	}
}
