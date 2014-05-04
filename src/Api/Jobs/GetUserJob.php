<?php
class GetUserJob extends AbstractUserJob
{
	public function execute()
	{
		return $this->user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::ViewUser,
			Access::getIdentity($this->user));
	}
}
