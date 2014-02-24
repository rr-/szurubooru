<?php
class SqlExistsFunctor extends SqlUnaryFunctor
{
	public function getFunctionName()
	{
		return 'EXISTS';
	}

	public function getArgumentCount()
	{
		return 1;
	}
}
