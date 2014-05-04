<?php
class EditUserPasswordJob extends AbstractUserJob
{
	const NEW_PASSWORD = 'new-password';

	public function execute()
	{
		$user = $this->user;
		$newPassword = UserModel::validatePassword($this->getArgument(self::NEW_PASSWORD));

		$newPasswordHash = UserModel::hashPassword($newPassword, $user->passSalt);
		$oldPasswordHash = $user->passHash;
		if ($oldPasswordHash == $newPasswordHash)
			return $user;

		$user->passHash = $newPasswordHash;

		UserModel::save($user);

		LogHelper::log('{user} changed {subject}\'s password', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::ChangeUserPassword,
			Access::getIdentity($this->user),
		];
	}
}
