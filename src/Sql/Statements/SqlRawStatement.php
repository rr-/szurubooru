<?php
class SqlRawStatement extends SqlStatement
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
