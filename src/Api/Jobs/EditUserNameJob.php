<?php
class EditUserNameJob extends AbstractUserEditJob
{
	const NEW_USER_NAME = 'new-user-name';

	public function execute()
	{
		$user = $this->user;
		$newName = $this->getArgument(self::NEW_USER_NAME);

		$oldName = $user->name;
		if ($oldName == $newName)
			return $user;

		$user->name = $newName;
		UserModel::validateUserName($user);

		if (!$this->skipSaving)
			UserModel::save($user);

		Logger::log('{user} renamed {old} to {new}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'old' => TextHelper::reprUser($oldName),
			'new' => TextHelper::reprUser($newName)]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::ChangeUserName,
			Access::getIdentity($this->user));
	}
}
