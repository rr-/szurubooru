<?php
abstract class AbstractUserJob extends AbstractJob
{
	protected $user;

	public function prepare()
	{
		if ($this->hasArgument(JobArgs::ARG_USER_ENTITY))
		{
			$this->user = $this->getArgument(JobArgs::ARG_USER_ENTITY);
		}
		else
		{
			$userName = $this->getArgument(JobArgs::ARG_USER_NAME);
			$this->user = UserModel::getByNameOrEmail($userName);
		}
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::Alternative(
				JobArgs::ARG_USER_NAME,
				JobArgs::ARG_USER_EMAIL,
				JobArgs::ARG_USER_ENTITY),
			$this->getRequiredSubArguments());
	}

	public abstract function getRequiredSubArguments();
}
