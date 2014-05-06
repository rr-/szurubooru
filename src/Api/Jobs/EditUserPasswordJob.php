<?php
class EditUserPasswordJob extends AbstractUserJob
{
	const NEW_PASSWORD = 'new-password';

	public function isSatisfied()
	{
		return $this->hasArgument(self::NEW_PASSWORD);
	}

	public function execute()
	{
		$user = $this->user;
		$newPassword = $this->getArgument(self::NEW_PASSWORD);

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
