<?php
class SqlNoCaseFunctor extends SqlFunctionFunctor
{
	public function getFunctionName()
	{
		throw new Exception('Not implemented');
	}

	public function getArgumentCount()
	{
		return 1;
	}

	public function getAsString()
	{
		return $this->subjects[0]->getAsString() . ' COLLATE NOCASE';
	}
}
