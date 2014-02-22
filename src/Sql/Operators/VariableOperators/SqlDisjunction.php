<?php
class SqlDisjunction extends SqlVariableOperator
{
	public function getAsStringEmpty()
	{
		return '1';
	}

	public function getAsStringNonEmpty()
	{
		return '(' . join(' OR ', array_map(function($subject)
		{
			return $subject->getAsString();
		}, $this->subjects)) . ')';
	}
}
