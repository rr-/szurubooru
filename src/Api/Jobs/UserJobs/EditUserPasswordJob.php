<?php
class EditUserPasswordJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		$newPassword = $this->getArgument(JobArgs::ARG_NEW_PASSWORD);

		$oldPasswordHash = $user->getPasswordHash();
		$user->setPassword($newPassword);
		$newPasswordHash = $user->getPasswordHash();
		if ($oldPasswordHash == $newPasswordHash)
			return $user;

		if ($this->getContext() == self::CONTEXT_NORMAL)
			UserModel::save($user);

		Logger::log('{user} changed {subject}\'s password', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);

		return $user;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->userRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_PASSWORD);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::RegisterAccount
				: Privilege::ChangeUserPassword,
			Access::getIdentity($this->userRetriever->retrieve()));
	}
}
