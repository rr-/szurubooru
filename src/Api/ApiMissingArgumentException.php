<?php
class ApiMissingArgumentException extends SimpleException
{
	public function __construct($argumentName)
	{
		parent::__construct('Expected argument "' . $argumentName . '" was not specified');
	}
}
