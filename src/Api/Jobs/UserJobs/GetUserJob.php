<?php
class GetUserJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		return $this->userRetriever->retrieve();
	}

	public function getRequiredArguments()
	{
		return $this->userRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::ViewUser,
			Access::getIdentity($this->userRetriever->retrieve()));
	}
}
