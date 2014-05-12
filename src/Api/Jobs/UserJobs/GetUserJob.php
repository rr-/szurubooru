<?php
class GetUserJob extends AbstractUserJob
{
	public function execute()
	{
		return $this->user;
	}

	public function getRequiredSubArguments()
	{
		return null;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::ViewUser,
			Access::getIdentity($this->user));
	}
}
