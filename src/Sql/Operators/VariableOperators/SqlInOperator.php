<?php
class SqlInOperator extends SqlVariableOperator
{
	protected $subject;

	public function __construct($subject)
	{
		$this->subject = $this->attachExpression($subject);
	}

	public function getAsStringEmpty()
	{
		return '0';
	}

	public function getAsStringNonEmpty()
	{
		return '(' . $this->subject->getAsString() . ') IN (' . join(', ', array_map(function($subject)
		{
			return $subject->getAsString();
		}, $this->subjects)) . ')';
	}
}
