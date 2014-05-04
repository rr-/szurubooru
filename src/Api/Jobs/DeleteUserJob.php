<?php
class DeleteUserJob extends AbstractUserJob
{
	public function execute()
	{
		$user = $this->user;

		$name = $user->getName();
		UserModel::remove($user);

		Logger::log('{user} removed {subject}\'s account', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($name)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::DeleteUser,
			Access::getIdentity($this->user));
	}
}
