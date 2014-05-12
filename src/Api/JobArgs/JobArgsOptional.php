<?php
class JobArgsOptional extends JobArgsNestedStruct
{
	/**
	* Simplifies the structure as much as possible
	* and returns new class or existing args.
	*/
	public static function factory(array $args)
	{
		$args = array_filter($args, function($arg)
		{
			return $arg !== null;
		});

		if (count($args) == 0)
			return null;
		else
			return new self($args);
	}
}
