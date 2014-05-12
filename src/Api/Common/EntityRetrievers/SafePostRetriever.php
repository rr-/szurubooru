<?php
class SafePostRetriever implements IEntityRetriever
{
	private $job;

	public function __construct(IJob $job)
	{
		$this->job = $job;
	}

	public function getJob()
	{
		return $this->job;
	}

	public function tryRetrieve()
	{
		if ($this->job->hasArgument(JobArgs::ARG_POST_ENTITY))
			return $this->job->getArgument(JobArgs::ARG_POST_ENTITY);

		if ($this->job->hasArgument(JobArgs::ARG_POST_NAME))
			return PostModel::getByName($this->job->getArgument(JobArgs::ARG_POST_NAME));

		return null;
	}

	public function retrieve()
	{
		$post = $this->tryRetrieve();
		if ($post)
			return $post;
		throw new ApiJobUnsatisfiedException($this->job);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_POST_NAME,
			JobArgs::ARG_POST_ENTITY);
	}
}
