<?php
class AbstractTest
{
	public $assert;

	public function __construct()
	{
		$this->assert = new Assert();
	}
}
