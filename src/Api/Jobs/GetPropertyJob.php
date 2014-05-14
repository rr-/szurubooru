<?php
class GetPropertyJob extends AbstractJob
{
	public function execute()
	{
		return PropertyModel::get($this->getArgument(JobArgs::ARG_QUERY));
	}

	public function getRequiredArguments()
	{
		return JobArgs::ARG_QUERY;
	}

	public function getRequiredPrivileges()
	{
		return false;
	}
}
