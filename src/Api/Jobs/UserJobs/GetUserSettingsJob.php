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

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::ChangeUserSettings,
			Access::getIdentity($this->userRetriever->retrieve()));
	}

	public function isAuthenticationRequired()
	{
		return false;
	}
}
