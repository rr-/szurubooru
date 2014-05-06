<?php
class ApiJobUnsatisfiedException extends SimpleException
{
	public function __construct(AbstractJob $job)
	{
		parent::__construct(get_class($job) . ' cannot be run due to unsatisfied execution conditions.');
	}
}
