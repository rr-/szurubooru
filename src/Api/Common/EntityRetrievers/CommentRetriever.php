<?php
class CommentRetriever implements IEntityRetriever
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
		if ($this->job->hasArgument(JobArgs::ARG_COMMENT_ENTITY))
			return $this->job->getArgument(JobArgs::ARG_COMMENT_ENTITY);

		if ($this->job->hasArgument(JobArgs::ARG_COMMENT_ID))
			return CommentModel::getById($this->job->getArgument(JobArgs::ARG_COMMENT_ID));

		return null;
	}

	public function retrieve()
	{
		$comment = $this->tryRetrieve();
		if ($comment)
			return $comment;
		throw new ApiJobUnsatisfiedException($this->job);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_COMMENT_ID,
			JobArgs::ARG_COMMENT_ENTITY);
	}
}
