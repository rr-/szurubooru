<?php
class SqlMaxFunctor extends SqlUnaryFunctor
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
