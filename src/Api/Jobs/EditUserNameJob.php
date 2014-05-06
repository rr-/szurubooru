<?php
class EditUserNameJob extends AbstractUserJob
{
	const NEW_USER_NAME = 'new-user-name';

	public function isSatisfied()
	{
		return $this->hasArgument(self::NEW_USER_NAME);
	}

	public function execute()
	{
		$user = $this->user;
		$newName = $this->getArgument(self::NEW_USER_NAME);

		$oldName = $user->getName();
		if ($oldName == $newName)
			return $user;

		$user->setName($newName);
		UserModel::validateUserName($user);

		if ($this->getContext() == self::CONTEXT_NORMAL)
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
