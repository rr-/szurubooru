<?php
abstract class SqlUnaryOperator extends SqlOperator
{
	protected $subject;

	public function __construct($subject)
	{
		$this->subject = $this->attachExpression($subject);
	}

	public function getAsString()
	{
		if (empty($this->subject->getAsString()))
			return $this->getAsStringEmpty();

		return $this->getAsStringNonEmpty();
	}

	public function getAsStringEmpty()
	{
		return '';
	}

	public abstract function getAsStringNonEmpty();
}
