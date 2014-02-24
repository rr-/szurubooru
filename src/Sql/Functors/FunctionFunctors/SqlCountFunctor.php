<?php
class SqlCountFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'COUNT';
	}

	public function getArgumentCount()
	{
		return 1;
	}
}
