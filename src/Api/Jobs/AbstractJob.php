<?php
abstract class AbstractJob implements IJob
{
	const CONTEXT_NORMAL = 1;
	const CONTEXT_BATCH_EDIT = 2;
	const CONTEXT_BATCH_ADD = 3;

	protected $arguments = [];
	protected $context = self::CONTEXT_NORMAL;
	protected $subJobs;

	public function prepare()
	{
	}

	public abstract function execute();

	public abstract function getRequiredArguments();

	public function getName()
	{
		$name = get_called_class();
		$name = str_replace('Job', '', $name);
		$name = TextCaseConverter::convert(
			$name,
			TextCaseConverter::UPPER_CAMEL_CASE,
			TextCaseConverter::SPINAL_CASE);
		return $name;
	}

	public function addSubJob(IJob $subJob)
	{
		$this->subJobs []= $subJob;
	}

	public function getSubJobs()
	{
		return $this->subJobs;
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
