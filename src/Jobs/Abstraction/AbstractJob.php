<?php
abstract class AbstractJob
{
	protected $arguments;

	public function prepare()
	{
	}

	public abstract function execute();

	public abstract function requiresAuthentication();
	public abstract function requiresConfirmedEmail();
	public abstract function requiresPrivilege();

	public function getArgument($key)
	{
		if (!isset($this->arguments[$key]))
			throw new SimpleException('Expected argument "' . $key . '" was not specified');

		return $this->arguments[$key];
	}

	public function setArguments($arguments)
	{
		$this->arguments = $arguments;
	}
}
