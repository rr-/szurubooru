<?php
class SqlIfNullOperator extends SqlOperator
{
	protected $subject;
	protected $target;

	public function __construct($subject, $target)
	{
		$this->subject = $this->attachExpression($subject);
		$this->target = $this->attachExpression($target);
	}

	public function getAsString()
	{
		return 'IFNULL (' . $this->subject->getAsString() . ', ' . $this->target->getAsString() . ')';
	}
}
