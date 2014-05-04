<?php
class EditUserNameJob extends AbstractUserJob
{
	const NEW_USER_NAME = 'new-user-name';

	public function execute()
	{
		$user = $this->user;
		$newName = UserModel::validateUserName($this->getArgument(self::NEW_USER_NAME));

		$oldName = $user->name;
		if ($oldName == $newName)
			return $user;

		$user->name = $newName;

		UserModel::save($user);

		LogHelper::log('{user} renamed {old} to {new}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'old' => TextHelper::reprUser($oldName),
			'new' => TextHelper::reprUser($newName)]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::ChangeUserName,
			Access::getIdentity($this->user),
		];
	}
}
