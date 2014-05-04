<?php
class GetUserJob extends AbstractUserJob
{
	public function execute()
	{
		return $this->user;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::ViewUser,
			Access::getIdentity($this->user)
		];
	}
}
