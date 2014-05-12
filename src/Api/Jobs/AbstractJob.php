<?php
abstract class AbstractJob implements IJob
{
	const CONTEXT_NORMAL = 1;
	const CONTEXT_BATCH_EDIT = 2;
	const CONTEXT_BATCH_ADD = 3;

	protected $arguments = [];
	protected $context = self::CONTEXT_NORMAL;

	public function prepare()
	{
	}

	public abstract function execute();

	public abstract function getRequiredArguments();

	public function getRequiredPrivileges()
	{
		return false;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}

	public function getContext()
	{
		return $this->context;
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getArgument($key)
	{
		if (!$this->hasArgument($key))
			throw new ApiMissingArgumentException($key);

		return $this->arguments[$key];
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function hasArgument($key)
	{
		return isset($this->arguments[$key]);
	}

	public function setArgument($key, $value)
	{
		$this->arguments[$key] = $value;
	}

	public function setArguments(array $arguments)
	{
		$this->arguments = $arguments;
	}
}
