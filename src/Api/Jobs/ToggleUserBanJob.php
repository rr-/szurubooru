<?php
class ToggleUserBanJob extends AbstractUserJob
{
	public function execute()
	{
		$user = $this->user;
		$banned = boolval($this->getArgument(self::STATE));

		$user->banned = $banned;
		UserModel::save($user);

		LogHelper::log(
			$banned
				? '{user} banned {subject}'
				: '{user} unbanned {subject}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::BanUser,
			Access::getIdentity($this->user));
	}
}
