<?php
class SqlMaxFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'MAX';
	}

	public function getArgumentCount()
	{
		return 1;
	}
}
