<?php
class EditUserNameJob extends AbstractUserJob
{
	public function isSatisfied()
	{
		return $this->hasArgument(JobArgs::ARG_NEW_USER_NAME);
	}

	public function execute()
	{
		$user = $this->user;
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

	public function requiresPrivilege()
	{
		return new Privilege(
			$this->getContext() == self::CONTEXT_BATCH_ADD
				? Privilege::RegisterAccount
				: Privilege::ChangeUserName,
			Access::getIdentity($this->user));
	}
}
