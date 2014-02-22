<?php
class SqlBinaryOperator extends SqlOperator
{
	protected $subject;
	protected $target;
	protected $operator;

	public function __construct($subject, $target, $operator)
	{
		$this->subject = $this->attachExpression($subject);
		$this->target = $this->attachExpression($target);
		$this->operator = $operator;
	}

	public function getAsString()
	{
		return '(' . $this->subject->getAsString() . ') ' . $this->operator . ' (' . $this->target->getAsString() . ')';
	}
}
