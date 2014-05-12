<?php
class ApiJobUnsatisfiedException extends SimpleException
{
	public function __construct(AbstractJob $job, $arg = null)
	{
		parent::__construct('%s cannot be run due to unsatisfied execution conditions (%s).',
			get_class($job),
			$arg);
	}
}
