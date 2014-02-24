<?php
class SqlInFunctor extends SqlVariableFunctor
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
		return self::surroundBraces($this->subject) . ' IN (' . join(', ', array_map(function($subject)
		{
			return self::surroundBraces($subject);
		}, $this->subjects)) . ')';
	}
}
