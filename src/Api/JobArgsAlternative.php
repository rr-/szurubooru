<?php
class JobArgsAlternative extends JobArgsNestedStruct
{
	/**
	* simplifies the structure as much as possible
	* and returns new class or existing args.
	*/
	public static function factory(array $args)
	{
		$finalArgs = [];

		foreach ($args as $arg)
		{
			if ($arg instanceof self)
				$finalArgs = array_merge($finalArgs, $arg->args);
			elseif ($arg !== null)
				$finalArgs []= $arg;
		}

		if (count($finalArgs) == 1)
			return $finalArgs[0];
		else
			return new self($finalArgs);
	}
}
