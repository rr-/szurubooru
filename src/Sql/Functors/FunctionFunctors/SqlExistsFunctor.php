<?php
class SqlExistsFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		return 'EXISTS';
	}

	public function getArgumentCount()
	{
		return 1;
	}

	public function getAsString()
	{
		return $this->getFunctionName() . ' (' . $this->subjects[0]->getAsString() . ')';
	}
}
