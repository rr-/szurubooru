<?php
class JobArgsNestedStruct
{
	public $args;

	protected function __construct(array $args)
	{
		usort($args, function($arg1, $arg2)
		{
			return strnatcasecmp(serialize($arg1), serialize($arg2));
		});
		$this->args = $args;
	}

	public static function factory(array $args)
	{
		throw new BadMethodCallException('Not implemented');
	}
}
