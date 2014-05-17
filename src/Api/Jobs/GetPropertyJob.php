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

	public function getRequiredMainPrivilege()
	{
		return null;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}

}
