<?php
class GetUserSettingsJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		return $user->getSettings()->getAllAsArray();
	}

	public function getRequiredArguments()
	{
		return $this->userRetriever->getRequiredArguments();
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::EditUserSettings;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->userRetriever->retrieve());
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
