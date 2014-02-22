<?php
class SqlConjunction extends SqlVariableOperator
{
	public function getAsStringEmpty()
	{
		return '1';
	}

	public function getAsStringNonEmpty()
	{
		return '(' . join(' AND ', array_map(function($subject)
		{
			return $subject->getAsString();
		}, $this->subjects)) . ')';
	}
}
