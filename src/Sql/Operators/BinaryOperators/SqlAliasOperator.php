<?php
class SqlAliasOperator extends SqlBinaryOperator
{
	public function __construct($subject, $target)
	{
		parent::__construct($subject, $target, 'AS');
	}

	public function getAsString()
	{
		return '(' . $this->subject->getAsString() . ') ' . $this->operator . ' ' . $this->target->getAsString();
	}
}
