<?php
class SqlSubstrFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'SUBSTR';
	}

	public function getArgumentCount()
	{
		return [2, 3];
	}
}
