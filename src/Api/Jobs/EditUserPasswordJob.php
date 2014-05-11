<?php
class EditUserPasswordJob extends AbstractUserJob
{
	public function isSatisfied()
	{
		return $this->hasArgument(JobArgs::ARG_NEW_PASSWORD);
	}

	public function execute()
	{
		$user = $this->user;
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

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::RegisterAccount
				: Privilege::ChangeUserPassword,
			Access::getIdentity($this->user));
	}
}
