<?php
class SqlStringExpression extends SqlExpression
{
	protected $text;

	public function __construct($text)
	{
		$this->text = $text;
	}

	public function getAsString()
	{
		return $this->text;
	}
}
