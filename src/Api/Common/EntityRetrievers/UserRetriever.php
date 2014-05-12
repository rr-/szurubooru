<?php
class UserRetriever implements IEntityRetriever
{
	private $job;

	public function __construct(IJob $job)
	{
		$this->job = $job;
	}

	public function tryRetrieve()
	{
		if ($this->job->hasArgument(JobArgs::ARG_USER_ENTITY))
			return $this->job->getArgument(JobArgs::ARG_USER_ENTITY);

		if ($this->job->hasArgument(JobArgs::ARG_USER_NAME))
			return UserModel::getByNameOrEmail($this->job->getArgument(JobArgs::ARG_USER_NAME));

		return null;
	}

	public function retrieve()
	{
		$user = $this->tryRetrieve();
		if ($user)
			return $user;
		throw new ApiJobUnsatisfiedException($this->job);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_USER_NAME,
			JobArgs::ARG_USER_EMAIL,
			JobArgs::ARG_USER_ENTITY);
	}
}
