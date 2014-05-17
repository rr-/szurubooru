<?php
class ToggleUserBanJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		$banned = TextHelper::toBoolean($this->getArgument(JobArgs::ARG_NEW_STATE));

		if ($banned)
			$user->ban();
		else
			$user->unban();
		UserModel::save($user);

		Logger::log(
			$banned
				? '{user} banned {subject}'
				: '{user} unbanned {subject}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);

		return $user;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->userRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_STATE);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::BanUser;
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
