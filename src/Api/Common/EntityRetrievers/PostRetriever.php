<?php
class PostRetriever implements IEntityRetriever
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

		if ($this->job->hasArgument(JobArgs::ARG_POST_ID))
			return PostModel::getById($this->job->getArgument(JobArgs::ARG_POST_ID));

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

	public function retrieveForEditing()
	{
		$post = $this->retrieve();
		if ($this->job->getContext() === IJob::CONTEXT_BATCH_ADD)
			return $post;

		$expectedRevision = $this->job->getArgument(JobArgs::ARG_POST_REVISION);
		if ($expectedRevision != $post->getRevision())
			throw new SimpleException('This post was already edited by someone else in the meantime');

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_POST_ID,
			JobArgs::ARG_POST_NAME,
			JobArgs::ARG_POST_ENTITY);
	}

	public function getRequiredArgumentsForEditing()
	{
		if ($this->job->getContext() === IJob::CONTEXT_BATCH_ADD)
			return $this->getRequiredArguments();

		return JobArgs::Conjunction(
			$this->getRequiredArguments(),
			JobArgs::ARG_POST_REVISION);
	}
}
