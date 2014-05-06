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
		$newPassword = UserModel::validatePassword($this->getArgument(self::NEW_PASSWORD));

		$newPasswordHash = UserModel::hashPassword($newPassword, $user->passSalt);
		$oldPasswordHash = $user->passHash;
		if ($oldPasswordHash == $newPasswordHash)
			return $user;

		$user->passHash = $newPasswordHash;

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
			Privilege::ChangeUserPassword,
			Access::getIdentity($this->user));
	}
}
