<?php
class EditUserNameJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		$newName = $this->getArgument(JobArgs::ARG_NEW_USER_NAME);

		$oldName = $user->getName();
		if ($oldName == $newName)
			return $user;

		$user->setName($newName);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			UserModel::save($user);

		Logger::log('{user} renamed {old} to {new}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'old' => TextHelper::reprUser($oldName),
			'new' => TextHelper::reprUser($newName)]);

		return $user;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->userRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_USER_NAME);
	}

	public function getRequiredMainPrivilege()
	{
		return $this->getContext() == self::CONTEXT_BATCH_ADD
			? Privilege::RegisterAccount
			: Privilege::EditUserName;
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
