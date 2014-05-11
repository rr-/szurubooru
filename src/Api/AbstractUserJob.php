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
}
