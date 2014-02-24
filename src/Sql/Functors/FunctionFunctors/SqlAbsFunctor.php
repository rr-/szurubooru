<?php
class SqlAbsFunctor extends SqlUnaryFunctor
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
